import { library, dom } from '@fortawesome/fontawesome-svg-core';

// Import solid weight icons
import {
	faCheck as fasCheck,
	faAngleLeft as fasAngleLeft,
} from '@fortawesome/free-solid-svg-icons';

export function register() {
	// Add icons to library
	library.add(
		fasCheck,
		fasAngleLeft,
	);

	// Replace any existing <i> tags with <svg> and set up a MutationObserver to
	// continue doing this as the DOM changes.
	dom.watch();
};
