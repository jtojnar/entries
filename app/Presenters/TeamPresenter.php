<?php

declare(strict_types=1);

namespace App\Presenters;

use App;
use App\Components\SportidentControl;
use App\Exporters;
use App\Helpers\EmailFactory;
use App\Helpers\SpaydQrGenerator;
use App\Model\Configuration\Entries;
use App\Model\Configuration\Fields;
use App\Model\InvoiceModifier;
use App\Model\Orm\Invoice\Invoice;
use App\Model\Orm\ItemReservation\ItemReservation;
use App\Model\Orm\Team\Team;
use DateTimeImmutable;
use Exception;
use Kdyby\Replicator\Container as ReplicatorContainer;
use Nette;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Forms\Controls;
use Nette\Mail\Message;
use Nette\Utils\Html;
use Nextras\FormsRendering\Renderers\FormLayout;
use stdClass;
use Tracy\Debugger;

/**
 * The main presenter of the application.
 */
final class TeamPresenter extends BasePresenter {
	#[Inject]
	public App\Model\Orm\Country\CountryRepository $countries;

	#[Inject]
	public App\Model\Orm\Team\TeamRepository $teams;

	#[Inject]
	public App\Model\Orm\Person\PersonRepository $persons;

	#[Inject]
	public EmailFactory $emailFactory;

	#[Inject]
	public SpaydQrGenerator $spaydQrGenerator;

	#[Inject]
	public Entries $entries;

	#[Inject]
	public App\Model\Orm\Invoice\InvoiceRepository $invoices;

	#[Inject]
	public App\Model\Orm\ItemReservation\ItemReservationRepository $itemReservations;

	#[Inject]
	public App\Templates\Filters\CategoryFormatFilter $categoryFormatter;

	#[Inject]
	public App\Forms\FormFactory $formFactory;

	#[Inject]
	public App\Forms\TeamFormFactory $teamFormFactory;

	#[Inject]
	public Nette\Security\Passwords $passwords;

	#[Inject]
	public Nette\Http\Request $request;

	#[Inject]
	public Nette\Http\Response $response;

	#[Inject]
	public Nette\Mail\Mailer $mailer;

	#[Inject]
	public Nette\DI\Container $context;

	public function startup(): void {
		if (($this->action === 'register' || $this->action === 'edit') && !$this->user->isInRole('admin')) {
			$today = new DateTimeImmutable();
			if ($this->entries->closing !== null && $this->entries->closing < $today) {
				throw new App\Exceptions\TooLateForAccessException();
			} elseif ($this->entries->opening !== null && $this->entries->opening > $today) {
				throw new App\Exceptions\TooSoonForAccessException();
			}
		}
		parent::startup();
	}

	public function actionList(): void {
		$where = [];
		$category = $this->request->getQuery('category');
		if ($category !== null) {
			\assert(\is_string($category)); // For PHPStan.
			$where = ['category' => explode('|', $category)];
		}

		match ($this->request->getQuery('status')) {
			'paid' => $where['status'] = 'paid',
			'registered' => $where['status'] = 'registered',
			default => null,
		};

		if (!isset($where['status'])) {
			$where['status!='] = 'withdrawn';
		}

		/** @var Nette\Bridges\ApplicationLatte\DefaultTemplate $template */
		$template = $this->template;

		$template->teams = $this->teams->findBy($where);

		$template->getLatte()->addFilter('personData', $this->personData(...));
		$template->getLatte()->addFilter('teamData', $this->teamData(...));

		$template->stats = ['count' => \count($template->teams)];
	}

	public function renderRegister(): void {
		$form = $this->getComponent('teamForm');
		if ($form->isSubmitted() === false) {
			// Create sufficient number of person subforms for the most common team size (when it is greater than minimum team size).
			$remainingMembers = $this->entries->initialMembers - $this->entries->minMembers;
			/** @var ReplicatorContainer */
			$replicator = $form['persons'];
			for ($i = $remainingMembers; $i > 0; --$i) {
				$replicator->createOne();
			}
		}
	}

	public function renderEdit(?int $id = null): void {
		if (!$this->user->isLoggedIn()) {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		} else {
			/** @var Nette\Security\SimpleIdentity $identity */
			$identity = $this->user->identity;

			if ($id === null) {
				$this->redirect('edit', ['id' => $identity->id]);
			}
			if (!$this->user->isInRole('admin') && $identity->id !== $id) {
				$backlink = $this->storeRequest('+ 48 hours');
				$this->redirect('Sign:in', ['backlink' => $backlink]);
			}

			$team = $this->teams->getById($id);
			if ($team === null) {
				$this->error($this->translator->translate('messages.team.edit.error.404'));
			}
			if (!$this->user->isInRole('admin') && $team->status === 'paid') {
				$this->flashMessage($this->translator->translate('messages.team.edit.error.already_paid'), 'error');
				$this->redirect('Homepage:');
			}

			$form = $this->getComponent('teamForm');
			if ($form->isSubmitted() === false) {
				$default = [];
				$default['name'] = $team->name;
				$default['category'] = $team->category;
				$default['message'] = $team->message;
				$default['persons'] = [];

				$fields = $this->entries->teamFields;
				foreach ($fields as $field) {
					$name = $field->name;
					if (isset($team->getJsonData()->$name)) {
						$default[$name] = $team->getJsonData()->$name;
					} elseif ($field instanceof Fields\SportidentField) {
						$default[$name] = [
							SportidentControl::NAME_NEEDED => true,
						];
					}
				}

				$fields = $this->entries->personFields;
				foreach ($team->persons as $person) {
					$personDefault = [
						'firstname' => $person->firstname,
						'lastname' => $person->lastname,
						'gender' => $person->gender,
						'email' => $person->email,
						'birth' => $person->birth,
					];
					if ($this->entries->allowPlaceholders) {
						$personDefault['placeholder'] = $person->placeholder;
					}

					foreach ($fields as $field) {
						$name = $field->name;
						if (isset($person->getJsonData()->$name)) {
							$personDefault[$name] = $person->getJsonData()->$name;
						} elseif ($field instanceof Fields\SportidentField) {
							$personDefault[$name] = [
								SportidentControl::NAME_NEEDED => true,
							];
						}
					}

					$default['persons'][] = $personDefault;
				}
				$form->setValues($default);
			}
		}
	}

	public function actionConfirm(int $id): void {
		if ($this->user->isInRole('admin')) {
			$team = $this->teams->getById($id);
			if ($team === null) {
				$this->error($this->translator->translate('messages.team.edit.error.404'));
			}
			if ($team->status === 'registered') {
				$team->status = 'paid';
				$team->lastInvoice->status = Invoice::STATUS_PAID;
				$this->teams->persistAndFlush($team);
				$this->redirect('list');
			} else {
				$this->flashMessage($this->translator->translate('messages.team.edit.error.already_paid'), 'info');
				$this->redirect('Homepage:');
			}
		} else {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		}
	}

	public function actionExport(string $type = 'csv'): void {
		if (!$this->user->isInRole('admin')) {
			$backlink = $this->storeRequest('+ 48 hours');
			$this->redirect('Sign:in', ['backlink' => $backlink]);
		}

		$where = [];

		$category = $this->request->getQuery('category');
		if ($category !== null) {
			\assert(\is_string($category)); // For PHPStan.
			$where = ['category' => explode('|', $category)];
		}

		match ($this->request->getQuery('status')) {
			'paid' => $where['status'] = 'paid',
			'registered' => $where['status'] = 'registered',
			'all' => null,
			default => $where['status!='] = 'withdrawn',
		};

		$teams = $this->teams->findBy($where);

		$teamFields = $this->entries->teamFields;
		$personFields = $this->entries->personFields;

		if (\count($teams) > 0) {
			if ($type === 'meos') {
				$exporter = new Exporters\MeosExporter($teams, $this->categoryFormatter);
				$this->response->setContentType($exporter->getMimeType(), 'UTF-8');
				$exporter->output();
			} else {
				$exporter = new Exporters\CsvExporter($teams, $this->countries, $teamFields, $personFields, $this->categoryFormatter);
				$this->response->setContentType('text/plain', 'UTF-8');
				$exporter->output();
			}
		} else {
			$this->flashMessage('messages.team.list.empty', 'error');
			$this->redirect('list');
		}
	}

	protected function createComponentTeamForm(): Form {
		$idParam = $this->getParameter('id');
		$isEditing = $idParam !== null;
		$reservationStats = $this->itemReservations->getStats();

		if ($isEditing) {
			\assert(\is_string($idParam)); // For PHPStan.
			$id = (int) $idParam;
			$team = $this->teams->getById($id);
			if ($team === null) {
				$this->error($this->translator->translate('messages.team.edit.error.404'));
			}

			// Unlock items reserved by this team to it.
			foreach ($team->itemReservations as $reservation) {
				--$reservationStats[$reservation->name];
			}
			foreach ($team->persons as $person) {
				foreach ($person->itemReservations as $reservation) {
					--$reservationStats[$reservation->name];
				}
			}
		}

		$form = $this->teamFormFactory->create(
			countries: $this->countries->fetchIdNamePairs(),
			reservationStats: $reservationStats,
			canModifyLocked: $this->getUser()->isInRole('admin'),
			isEditing: $isEditing,
		);

		/** @var Controls\SubmitButton */
		$save = $form['save'];
		$save->onClick[] = $this->processTeamForm(...);

		return $form;
	}

	private function processTeamForm(Controls\SubmitButton $button): void {
		$today = new DateTimeImmutable();
		if (!$this->user->isInRole('admin')) {
			if ($this->entries->closing !== null && $this->entries->closing < $today) {
				throw new App\Exceptions\TooLateForAccessException();
			} elseif ($this->entries->opening !== null && $this->entries->opening > $today) {
				throw new App\Exceptions\TooSoonForAccessException();
			}
		}

		/** @var App\Components\TeamForm $form */
		$form = $button->form;
		/** @var array */ // actually \ArrayAccess but PHPStan does not handle that very well.
		$values = $form->getValues();
		/** @var string $password */
		$password = null;

		$this->cleanNonApplicableFields($form);

		if ($this->action === 'edit') {
			if (!$this->user->isLoggedIn()) {
				throw new ForbiddenRequestException();
			}

			$idParam = $this->getParameter('id');
			\assert(\is_string($idParam)); // For PHPStan.
			$id = (int) $idParam;
			$team = $this->teams->getById($id);

			/** @var Nette\Security\SimpleIdentity $identity */
			$identity = $this->user->identity;

			if ($team === null) {
				$form->addError('messages.team.edit.error.404');

				return;
			} elseif (!$this->user->isInRole('admin') && $team->status !== Team::STATUS_REGISTERED) {
				$form->addError(
					match ($team->status) {
						Team::STATUS_PAID => 'messages.team.edit.error.already_paid',
						Team::STATUS_WITHDRAWN => 'messages.team.error.withdrawn',
					}
				);
			} elseif (!$this->user->isInRole('admin') && $identity->id !== $id) {
				$backlink = $this->storeRequest('+ 48 hours');
				$this->redirect('Sign:in', ['backlink' => $backlink]);
			}
		} else {
			$team = new Team();
			$password = Nette\Utils\Random::generate();
			$team->password = $this->passwords->hash($password);
			$team->ip = $this->request->remoteAddress ?? '';
		}

		try {
			$invoice = new Invoice();
			$invoice->status = Invoice::STATUS_NEW;
			$invoice->team = $team;
			$invoice->items = [];

			$team->name = $values['name'];
			$team->message = $values['message'];
			$team->category = $values['category'];

			$fields = $this->entries->teamFields;

			$limits = $this->entries->limits;
			$reservationStats = $this->itemReservations->getStats();
			foreach ($team->itemReservations as $reservation) {
				--$reservationStats[$reservation->name];
				$this->itemReservations->remove($reservation);
			}

			$jsonData = [];

			foreach ($fields as $field) {
				$name = $field->name;
				$jsonData[$name] = $values[$name];

				if ($field instanceof Fields\SportidentField && $field->fee !== null && (($jsonData[$name] ?? [])[SportidentControl::NAME_NEEDED] ?? null) === true) {
					$invoice->addItem(self::serializeInvoiceItem([
						'type' => $field->getType(),
						'scope' => 'team',
						'key' => $name,
					]), $field->fee);
				} elseif ($field instanceof Fields\CheckboxField && $jsonData[$name]) {
					if ($field->fee !== null) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $field->getType(),
							'scope' => 'team',
							'key' => $name,
						]), $field->fee);
					}

					if ($field->getLimitName() !== null) {
						$limitName = $field->getLimitName();
						$team->itemReservations->add(new ItemReservation($limitName));
						$reservationStats[$limitName] ??= 0;
						if (++$reservationStats[$limitName] > $limits[$limitName]) {
							/** @var Controls\BaseControl */
							$control = $form[$name];
							$control->addError('messages.team.field.error.no_longer_available');
						}
					}
				} elseif ($field instanceof Fields\EnumField && isset($field->options[$values[$name]]) && $jsonData[$name]) {
					$option = $field->options[$values[$name]];
					if ($option->fee !== null) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $field->getType(),
							'scope' => 'team',
							'key' => $name,
							'value' => $values[$name],
						]), $option->fee);
					}

					if ($option->getLimitName() !== null) {
						$limitName = $option->getLimitName();
						$team->itemReservations->add(new ItemReservation($limitName));
						$reservationStats[$limitName] ??= 0;
						if (++$reservationStats[$limitName] > $limits[$limitName]) {
							/** @var Controls\BaseControl */
							$control = $form[$name];
							$control->addError('messages.team.field.error.no_longer_available');
						}
					}
				} elseif ($field instanceof Fields\CheckboxlistField) {
					foreach ($jsonData[$name] as $item) {
						$option = $field->items[$item];
						if ($option->fee !== null) {
							$invoice->addItem(self::serializeInvoiceItem([
								'type' => $field->getType(),
								'scope' => 'team',
								'key' => $name,
								'value' => $item,
							]), $option->fee);
						}

						if ($option->getLimitName() !== null) {
							$limitName = $option->getLimitName();
							$team->itemReservations->add(new ItemReservation($limitName));
							$reservationStats[$limitName] ??= 0;
							if (++$reservationStats[$limitName] > $limits[$limitName]) {
								/** @var Controls\BaseControl */
								$control = $form[$name];
								$control->addError($this->translator->translate('messages.team.field.error.named_no_longer_available', null, ['item' => $this->translator->translate($option->label)]), false);
							}
						}
					}
				}
			}
			$team->setJsonData($jsonData);

			$this->teams->persist($team);

			if ($this->action === 'edit') {
				foreach ($team->persons as $person) {
					foreach ($person->itemReservations as $reservation) {
						--$reservationStats[$reservation->name];
						$this->itemReservations->remove($reservation);
					}
					$this->persons->remove($person);
				}
			}

			$personFee = $this->entries->categories->allCategories[$team->category]->fees->person;
			if ($personFee !== null) {
				$invoice->createItem(
					self::serializeInvoiceItem([
						'type' => '~entry',
						'scope' => 'person',
					]),
					$personFee,
				);
			}

			$fields = $this->entries->personFields;

			/** @var ?string $firstMemberAddress */
			$firstMemberAddress = null;
			/** @var ?string $firstMemberName */
			$firstMemberName = null;

			/** @var ReplicatorContainer */
			$replicator = $form['persons'];
			$personContainers = iterator_to_array($replicator->getContainers());
			foreach ($values['persons'] as $personKey => $member) {
				$personContainer = $personContainers[$personKey];
				$firstname = $member['firstname'];
				if ($firstMemberAddress === null) {
					$firstMemberAddress = $member['email'];
				}
				if ($firstMemberName === null) {
					$firstMemberName = $member['firstname'] . ' ' . $member['lastname'];
				}
				$person = new App\Model\Orm\Person\Person();

				$person->firstname = $firstname;
				$person->lastname = $member['lastname'];
				$person->gender = $member['gender'];
				$person->birth = $member['birth'];
				$person->email = $member['email'];
				$person->contact = \count($team->persons) === 0;
				$person->placeholder = $this->entries->allowPlaceholders && $member['placeholder'];
				$person->team = $team;

				$jsonData = [];
				foreach ($fields as $field) {
					$name = $field->name;
					$member[$name] ??= null;
					$jsonData[$name] = (!$this->user->isInRole('admin') && $form->isFieldDisabled($field)) ? $form->getDefaultFieldValue($field) : $member[$name];

					if ($field instanceof Fields\SportidentField && $field->fee !== null && (($jsonData[$name] ?? [])[SportidentControl::NAME_NEEDED] ?? null) === true) {
						$invoice->addItem(self::serializeInvoiceItem([
							'type' => $field->getType(),
							'scope' => 'person',
							'key' => $name,
						]), $field->fee);
					} elseif ($field instanceof Fields\CheckboxField && $jsonData[$name]) {
						if ($field->fee !== null) {
							$invoice->addItem(self::serializeInvoiceItem([
								'type' => $field->getType(),
								'scope' => 'person',
								'key' => $name,
							]), $field->fee);
						}

						if ($field->getLimitName() !== null) {
							$limitName = $field->getLimitName();
							$person->itemReservations->add(new ItemReservation($limitName));
							$reservationStats[$limitName] ??= 0;
							if (++$reservationStats[$limitName] > $limits[$limitName]) {
								/** @var Controls\BaseControl */
								$control = $personContainer[$name];
								$control->addError('messages.team.field.error.no_longer_available');
							}
						}
					} elseif ($field instanceof Fields\EnumField && isset($field->options[$member[$name]]) && $jsonData[$name]) {
						$option = $field->options[$member[$name]];
						if ($option->fee !== null) {
							$invoice->addItem(self::serializeInvoiceItem([
								'type' => $field->getType(),
								'scope' => 'person',
								'key' => $name,
								'value' => $member[$name],
							]), $option->fee);
						}

						if ($option->getLimitName() !== null) {
							$limitName = $option->getLimitName();
							$person->itemReservations->add(new ItemReservation($limitName));
							$reservationStats[$limitName] ??= 0;
							if (++$reservationStats[$limitName] > $limits[$limitName]) {
								/** @var Controls\BaseControl */
								$control = $personContainer[$name];
								$control->addError('messages.team.field.error.no_longer_available');
							}
						}
					} elseif ($field instanceof Fields\CheckboxlistField) {
						foreach ($jsonData[$name] as $item) {
							$option = $field->items[$item];
							if ($option->fee !== null) {
								$invoice->addItem(self::serializeInvoiceItem([
									'type' => $field->getType(),
									'scope' => 'person',
									'key' => $name,
									'value' => $item,
								]), $option->fee);
							}

							if ($option->getLimitName() !== null) {
								$limitName = $option->getLimitName();
								$person->itemReservations->add(new ItemReservation($limitName));
								$reservationStats[$limitName] ??= 0;
								if (++$reservationStats[$limitName] > $limits[$limitName]) {
									/** @var Controls\BaseControl */
									$control = $personContainer[$name];
									$control->addError($this->translator->translate('messages.team.field.error.named_no_longer_available', null, ['item' => $this->translator->translate($option->label)]), false);
								}
							}
						}
					}
				}

				$person->setJsonData($jsonData);

				$invoice->addItem(self::serializeInvoiceItem([
					'type' => '~entry',
					'scope' => 'person',
				]));
				$this->persons->persist($person);
			}

			/** @var ?class-string<InvoiceModifier> */
			$invoiceModifier = $this->entries->invoiceModifier;
			if ($invoiceModifier !== null) {
				$invoiceModifier::modify($team, $invoice, $this->entries);
			}

			foreach ($team->invoices as $inv) {
				if ($inv->status === Invoice::STATUS_NEW && $inv !== $invoice) {
					$inv->status = Invoice::STATUS_CANCELLED;
					$this->invoices->persist($inv);
				}
			}

			$this->invoices->persist($invoice);

			if (!$form->hasErrors()) {
				$this->teams->flush();

				if ($this->action === 'edit') {
					$this->flashMessage($this->translator->translate('messages.team.success.edit'));
				} else {
					/** @var Nette\Bridges\ApplicationLatte\DefaultTemplate $mtemplate */
					$mtemplate = $this->createTemplate();

					$appDir = $this->parameters->getAppDir();

					$baseMailTemplateLocalizedPath = $appDir . '/Templates/Mail/verification.' . $this->locale . '.latte';

					// If the override templates exist in the config directory,
					// let’s use them.
					$mailTemplatePath = null;
					if (file_exists($appDir . '/Config/mail/verification.' . $this->locale . '.latte')) {
						$mailTemplatePath = $appDir . '/Config/mail/verification.' . $this->locale . '.latte';
					} elseif (file_exists($appDir . '/Config/mail/verification.latte')) {
						$mailTemplatePath = $appDir . '/Config/mail/verification.latte';
					}

					// If not, let’s use the built-in templates.
					$baseMailTemplatePath = file_exists($baseMailTemplateLocalizedPath) ? $baseMailTemplateLocalizedPath : $appDir . '/Templates/Mail/verification.latte';
					if ($mailTemplatePath === null) {
						$mailTemplatePath = $baseMailTemplatePath;
					} else {
						// If the overrides exist, pass the built-in template as a parameter
						// so that they can inherit it and only override what they need.
						$mtemplate->layout = $baseMailTemplatePath;
						// TODO: Try to make use of coreParentFinder
						// so that the templates do not need to carry layout tags.
						// https://latte.nette.org/en/develop#toc-layout-lookup
					}

					$mtemplate->setFile($mailTemplatePath);

					// Define variables for use in the e-mail template.
					$mtemplate->accountNumber = $this->parameters->accountNumber;
					$mtemplate->accountNumberIban = $this->parameters->accountNumberIban !== null ? $this->parameters->accountNumberIban->asString() : null;
					$mtemplate->eventName =
						$this->parameters->getSiteTitle($this->locale)
						?? $this->parameters->getSiteTitle($this->translator->getDefaultLocale());
					$mtemplate->eventNameShort =
						$this->parameters->getSiteTitleShort($this->locale)
						?? $this->parameters->getSiteTitleShort($this->translator->getDefaultLocale())
						?? $mtemplate->eventName;
					\assert($mtemplate->eventNameShort !== null, 'Event name is required'); // For PHPStan
					$mtemplate->dateFormat = $this->translator->translate('messages.email.verification.entry_details.person.birth.format');
					$mtemplate->team = $team;
					$mtemplate->people = $team->persons;
					$mtemplate->id = $team->id;
					$mtemplate->name = $firstMemberName;
					$mtemplate->password = $password;
					$mtemplate->invoice = $invoice;
					$mtemplate->organiserMail = $this->parameters->getWebmasterEmail();
					$mtemplate->qrCode =
						$this->parameters->accountNumberIban !== null
						? Html::el(
							'img',
							[
								'src' => $this->spaydQrGenerator->generate(
									accountNumber: $this->parameters->accountNumberIban,
									amount: $invoice->getTotal(),
									eventName: $mtemplate->eventNameShort,
									teamId: $team->id,
								),
								'alt' => '',
							]
						) : null;

					// Inline styles into the e-mail
					$mailHtml = $this->emailFactory->create((string) $mtemplate);

					$mail = new Message();
					$mail
						->setFrom($mtemplate->organiserMail)
						->addTo($firstMemberAddress)
						->setHtmlBody($mailHtml, $this->spaydQrGenerator->getStoragePath());

					$mailer = $this->mailer;
					$mailer->send($mail);

					$this->flashMessage($this->translator->translate('messages.team.success.add', null, ['password' => $password]));
				}
				$this->redirect('Homepage:');
			}
		} catch (Nette\Application\AbortException $e) {
			throw $e;
		} catch (Exception $e) {
			Debugger::log($e);
			if ($this->action === 'edit') {
				$form->addError('messages.team.error.edit_general');
			} else {
				$form->addError('messages.team.error.add_general');
			}
		}
	}

	public function cleanNonApplicableFields(Nette\Forms\Form $form): void {
		$category = $form->values['category'];

		$teamFields = $this->entries->teamFields;
		foreach ($teamFields as $field) {
			$name = $field->name;
			if ($field->applicableCategories !== null && !\in_array($category, $field->applicableCategories, true)) {
				/** @var Controls\BaseControl */
				$control = $form[$name];
				$control->setValue(null);
			}
		}

		$personFields = $this->entries->personFields;
		$members = $form->values['persons'];
		\assert(is_iterable($members)); // For PHPStan.
		foreach ($members as $member) {
			foreach ($personFields as $field) {
				$name = $field->name;
				if ($field->applicableCategories !== null && !\in_array($category, $field->applicableCategories, true)) {
					/** @var Nette\Utils\ArrayHash */
					$persons = $form['persons'];
					/** @var Controls\BaseControl */
					$control = $persons[$name];
					$control->setValue(null);
				}
			}
		}
	}

	public function createComponentTeamListFilterForm(): Form {
		$form = $this->formFactory->create(FormLayout::INLINE);
		$form->setMethod($form::GET);

		$category = $form['category'] = new App\Components\CategoryEntry('messages.team.list.filter.category.label', $this->entries, true);
		$category->setPrompt('messages.team.list.filter.category.all');
		$category->setHtmlAttribute('style', 'width:auto;');

		if (($catParam = $this->request->getQuery('category')) !== '' && \assert($catParam === null || \is_string($catParam))) {
			// Set value based on query-string (even after we strip the do=teamListFilterForm-submit param).
			// Empty string (which corresponds to prompt) is ignored, since it cannot be passed to setValue.
			$category->setValue($catParam);
		}
		$category->controlPrototype->class[] = 'change-form-submit';

		if ($this->user->isInRole('admin')) {
			$status = $form->addSelect('status', 'messages.team.list.filter.status.label', ['registered' => 'messages.team.list.filter.status.registered', 'paid' => 'messages.team.list.filter.status.paid'])->setPrompt('messages.team.list.filter.status.all')->setHtmlAttribute('style', 'width:auto;');
			if (($statusParam = $this->request->getQuery('status')) !== '' && \assert($statusParam === null || \is_string($statusParam))) {
				// Set value based on query-string (even after we strip the do=teamListFilterForm-submit param).
				// Empty string (which corresponds to prompt) is ignored, since it cannot be passed to setValue.
				$status->setValue($statusParam);
			}
			$status->controlPrototype->class[] = 'change-form-submit';
		}

		$submit = $form->addSubmit('filter', 'messages.team.list.filter.submit.label');
		$submit->controlPrototype->class[] = 'noscript';
		$form->onValidate[] = $this->filterRedir(...);

		return $form;
	}

	/**
	 * Strips the do=teamListFilterForm-submit parameter from query string.
	 */
	private function filterRedir(Nette\Forms\Form $form): void {
		$parameters = [];

		$category = $this->request->getQuery('category');
		if ($category !== null && $category !== '') {
			$parameters['category'] = $category;
		}

		$status = $this->request->getQuery('status');
		if ($status !== null && $status !== '') {
			$parameters['status'] = $status;
		}

		if (\count($parameters) === 0) {
			$this->redirect('this');
		} else {
			$this->redirect('this', $parameters);
		}
	}

	public function createComponentTeamListActionForm(): Form {
		$form = $this->formFactory->create();

		foreach ($this->template->teams as $team) {
			$form->addCheckbox('team_' . $team->id);
		}

		$submit = $form->addSubmit('send_message', 'messages.team.list.action.send_message.label');

		$submit->onClick[] = $this->listActionSubmitMessage(...);

		return $form;
	}

	private function listActionSubmitMessage(Controls\SubmitButton $button): void {
		$values = $button->form->getValues();
		$selectedTeamIds = array_map(
			fn($name): string => substr((string) $name, \strlen('team_')),
			array_keys(
				array_filter(
					(array) $values,
					fn($value, $name): bool => str_starts_with((string) $name, 'team_') && \is_bool($value) && $value,
					\ARRAY_FILTER_USE_BOTH
				)
			)
		);

		if (\count($selectedTeamIds) === 0) {
			$this->redirect('this');
		} else {
			$this->redirect('Communication:compose', ['ids' => implode(', ', $selectedTeamIds)]);
		}
	}

	/**
	 * @return list<string>
	 */
	private function personData(stdClass $data): array {
		$fields = $this->entries->personFields;

		return $this->formatData($data, $fields);
	}

	/**
	 * @return list<string>
	 */
	private function teamData(stdClass $data): array {
		$fields = $this->entries->teamFields;

		return $this->formatData($data, $fields);
	}

	/**
	 * @param array<string, Fields\Field> $fields
	 *
	 * @return list<string>
	 */
	private function formatData(stdClass $data, array $fields): array {
		$ret = [];
		foreach ($fields as $field) {
			$name = $field->name;
			$label = $this->translator->translate($field->label);

			if (!$this->user->isInRole('admin') && !$field->public) {
				continue;
			}

			if ($field instanceof Fields\SportidentField) {
				$value = $data->$name->{SportidentControl::NAME_CARD_ID} ?? $this->translator->translate('messages.team.person.si.rent');
				$ret[] = $label . ' ' . $value;
				continue;
			} elseif ($field instanceof Fields\CountryField) {
				$country = isset($data->$name) ? $this->countries->getById($data->$name) : null;
				if ($country === null) {
					$ret[] = $this->translator->translate('messages.team.data.country.unknown');
					continue;
				}
				$ret[] = (string) Html::el('span', ['class' => 'fi fi-' . $country->codeAlpha2]) . ' ' . $country->name;
				continue;
			} elseif ($field instanceof Fields\EnumField && isset($data->$name) && isset($field->options[$data->$name])) {
				$selectedOption = \array_key_exists($data->$name, $field->options) ? $this->translator->translate($field->options[$data->$name]->label) : $data->$name;
				$ret[] = $label . ': ' . $selectedOption;
				continue;
			} elseif ($field instanceof Fields\CheckboxlistField && isset($data->$name)) {
				$items = array_map(
					fn(string $item): string => isset($field->items[$item]) ? $this->translator->translate($field->items[$item]->label) : $item,
					$data->$name
				);
				$ret[] = $label . ' ' . implode(', ', $items);
				continue;
			}
			if (isset($data->$name)) {
				$ret[] = $label . ' ' . $data->$name;
			}
		}

		return $ret;
	}

	/**
	 * @param array{scope?: string, type?: string, key?: string, value?: string} $item
	 */
	public static function serializeInvoiceItem(array $item): string {
		$parts = [$item['scope'] ?? '', $item['type'] ?? '', $item['key'] ?? '', $item['value'] ?? ''];

		return implode(':', $parts);
	}
}
