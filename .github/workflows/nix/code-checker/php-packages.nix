{composerEnv, fetchurl, fetchgit ? null, fetchhg ? null, fetchsvn ? null, noDev ? false}:

let
  packages = {
    "latte/latte" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "latte-latte-462444d669809528b6f6ce191b616d747c9b4bfc";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/latte/zipball/462444d669809528b6f6ce191b616d747c9b4bfc";
          sha256 = "0cd48vrxynf9h0a5q63x71qsj3k6701icp8admz6ahil43wnnlxg";
        };
      };
    };
    "nette/application" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-application-0729ede7e66fad642046a3eb670d368845272573";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/application/zipball/0729ede7e66fad642046a3eb670d368845272573";
          sha256 = "080g0j8jzlsgds1viip2mabliaqibz1wnhy6zc63fgsga69k3ds8";
        };
      };
    };
    "nette/caching" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-caching-6821d74c1db82c493c02c47f6485022d79b63176";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/caching/zipball/6821d74c1db82c493c02c47f6485022d79b63176";
          sha256 = "1v1xnzcmkh6pss7x24m3wkq0fy3sqigj7cbjvsk0l8vr16w84xpk";
        };
      };
    };
    "nette/code-checker" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-code-checker-ab56f7579bace93c194e163d9c7ce7183cb61391";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/code-checker/zipball/ab56f7579bace93c194e163d9c7ce7183cb61391";
          sha256 = "0rsjgdivbsyb2fxgh9a88spdp7qpds2w6idjg9fl4laispm5pyrp";
        };
      };
    };
    "nette/command-line" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-command-line-54335732059354fc35315774f33847253f10aa56";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/command-line/zipball/54335732059354fc35315774f33847253f10aa56";
          sha256 = "0rvc23wh3zx715fx9pyz1nzj0pkwjzfa3rh2d6n7gh0jd7fbjgk8";
        };
      };
    };
    "nette/component-model" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-component-model-9d97c0e1916bbf8e306283ab187834501fd4b1f5";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/component-model/zipball/9d97c0e1916bbf8e306283ab187834501fd4b1f5";
          sha256 = "1a099zy44sg3mxa40wxdyw4qrlldrmfdn6pxbrq2i5ll1zw6y9fy";
        };
      };
    };
    "nette/finder" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-finder-991aefb42860abeab8e003970c3809a9d83cb932";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/finder/zipball/991aefb42860abeab8e003970c3809a9d83cb932";
          sha256 = "182752n3fwp6k1f9x5i8zaw878phlz9v019lpxd2ja4z3ixpkrp0";
        };
      };
    };
    "nette/forms" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-forms-f373bcd5ea7a33672fa96035d4bf3110ab66ee44";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/forms/zipball/f373bcd5ea7a33672fa96035d4bf3110ab66ee44";
          sha256 = "0gylind3fgk7sg9zg6m5j8m876mwbyybbhnhg8n0ybcdgz6wxpbn";
        };
      };
    };
    "nette/http" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-http-d7cc833ee186d5139cde5aab43b39ee7aedd6f22";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/http/zipball/d7cc833ee186d5139cde5aab43b39ee7aedd6f22";
          sha256 = "1zisvb45sraqk0aipxrgfl7wk892zwrf0sbz4is07m55zd8kk2gf";
        };
      };
    };
    "nette/neon" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-neon-457bfbf0560f600b30d9df4233af382a478bb44d";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/neon/zipball/457bfbf0560f600b30d9df4233af382a478bb44d";
          sha256 = "06rfsyi8692fdnxvay949kvp6iyl28lldsrhclqxvr6i2ja99q38";
        };
      };
    };
    "nette/routing" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-routing-ff709ff9ed38a14c4fe3472534526593a8461ff5";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/routing/zipball/ff709ff9ed38a14c4fe3472534526593a8461ff5";
          sha256 = "0paaz21g3l0i31xfjiiq7z0jw7xv0lk768827xf3iaa7p7g0jksn";
        };
      };
    };
    "nette/utils" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-utils-a4175c62652f2300c8017fb7e640f9ccb11648d2";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/utils/zipball/a4175c62652f2300c8017fb7e640f9ccb11648d2";
          sha256 = "179w82y097gxmvpp3ks0wzhrdxbxkmk1klz9nbfdc9fn8bkwrrl1";
        };
      };
    };
  };
  devPackages = {};
in
composerEnv.buildPackage {
  inherit packages devPackages noDev;
  name = "nette-code-checker";
  src = composerEnv.filterSrc ./.;
  executable = true;
  symlinkDependencies = false;
  meta = {};
}
