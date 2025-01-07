{
  description = "Entry registration system for Rogaining";

  inputs = {
    nixpkgs.url = "github:cachix/devenv-nixpkgs/rolling";
    systems.url = "github:nix-systems/default";
    devenv.url = "github:cachix/devenv";
    devenv.inputs.nixpkgs.follows = "nixpkgs";

    composer2nixRepo = {
      url = "github:svanderburg/composer2nix";
      flake = false;
    };
  };

  outputs =
    {
      nixpkgs,
      devenv,
      systems,
      composer2nixRepo,
      ...
    }@inputs:

    let
      forEachSystem = nixpkgs.lib.genAttrs (import systems);
    in
    {
      devShells = forEachSystem (
        system:
        let
          pkgs = nixpkgs.legacyPackages.${system};

          importComposerPackage =
            path:
            (import path {
              inherit system pkgs;
              noDev = true;
            }).override
              {
                executable = true;
              };

          composer2nix = importComposerPackage composer2nixRepo.outPath;

          nette-code-checker = importComposerPackage ./.github/workflows/nix/code-checker;

          update-php-extradeps = pkgs.writeShellScriptBin "update-php-extradeps" ''
            pushd .github/workflows/nix/code-checker
            composer update
            env NIX_PATH=nixpkgs=${nixpkgs.outPath} ${composer2nix}/bin/composer2nix -p nette/code-checker
            popd
          '';

          php = pkgs.php81.withExtensions (
            { enabled, all }:
            with all;
            enabled
            ++ [
              intl
            ]
          );
        in
        {
          default = devenv.lib.mkShell {
            inherit inputs pkgs;
            modules = [
              {
                # https://devenv.sh/reference/options/
                packages = [
                  pkgs.python3 # for create-zipball.py
                  nette-code-checker
                  update-php-extradeps
                  pkgs.nodejs
                  pkgs.phpactor
                  php.packages.psalm
                ];

                languages.php = {
                  enable = true;
                  package = php;
                };
              }
            ];
          };
        }
      );
    };
}
