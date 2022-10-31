{
  description = "Entry registration system for Rogaining";

  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";

  inputs.utils.url = "github:numtide/flake-utils";

  inputs.composer2nixRepo = {
    url = "github:svanderburg/composer2nix";
    flake = false;
  };

  inputs.flake-compat = {
    url = "github:edolstra/flake-compat";
    flake = false;
  };

  outputs = { self, nixpkgs, utils, flake-compat, composer2nixRepo }:
    utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};

        importComposerPackage = path: (import path {
          inherit system pkgs;
          noDev = true;
        }).override {
          executable = true;
        };

        composer2nix = importComposerPackage composer2nixRepo.outPath;

        nette-code-checker = importComposerPackage ./.github/workflows/nix/code-checker;

        update-php-extradeps = pkgs.writeShellScriptBin "update-php-extradeps" ''
          pushd .github/workflows/nix/code-checker
          env NIX_PATH=nixpkgs=${nixpkgs.outPath} ${composer2nix}/bin/composer2nix -p nette/code-checker
          popd
        '';
      in {
        devShell =
          let
            php = pkgs.php81.withExtensions ({ enabled, all }: with all; enabled ++ [
              intl
            ]);
          in
            pkgs.mkShell {
              nativeBuildInputs = [
                php
                pkgs.python3 # for create-zipball.py
                nette-code-checker
                update-php-extradeps
              ] ++ (with pkgs.nodePackages; [
                yarn
              ]) ++ (with php.packages; [
                composer
                psalm
              ]);
            };
      }
    );
}
