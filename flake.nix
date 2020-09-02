{
  description = "Entry registration system for Rogaining";

  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";

  inputs.utils.url = "github:numtide/flake-utils";

  inputs.flake-compat = {
    url = "github:edolstra/flake-compat";
    flake = false;
  };

  outputs = { self, nixpkgs, utils, flake-compat }:
    utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
      in {
        devShell =
          let
            php = pkgs.php74.withExtensions ({ enabled, all }: with all; enabled ++ [
              intl
            ]);
          in
            pkgs.mkShell {
              nativeBuildInputs = [
                php
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
