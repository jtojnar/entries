{composerEnv, fetchurl, fetchgit ? null, fetchhg ? null, fetchsvn ? null, noDev ? false}:

let
  packages = {
    "latte/latte" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "latte-latte-bab49c360354f5e7e57c07ec1b1c9b2870a9bda4";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/latte/zipball/bab49c360354f5e7e57c07ec1b1c9b2870a9bda4";
          sha256 = "0k3pi7zrzbhkd2fl0i09aqml5hf31dl4a43q362rgsrcqx7agpid";
        };
      };
    };
    "nette/application" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-application-f50364147ca80d9fbcf4d6571cf216ced874d4f9";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/application/zipball/f50364147ca80d9fbcf4d6571cf216ced874d4f9";
          sha256 = "1k2pvi2npqz6gfkvz8g80p7lbb593fm7gvxw95pyydrs9a68vawy";
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
        name = "nette-command-line-4c67c727866f01983853cbb6dfca8666789f104c";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/command-line/zipball/4c67c727866f01983853cbb6dfca8666789f104c";
          sha256 = "01ckybl0cgd27hi5hj4wrz8nykbkql73qvi6i3xrncssr31vjq7j";
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
        name = "nette-neon-36e3f4f89fd8a7b89ada74c7a678baa9f7cc7719";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/neon/zipball/36e3f4f89fd8a7b89ada74c7a678baa9f7cc7719";
          sha256 = "144qah5gvc5h4k020gdhsgcbxnjxx1cri4k3gmr49dxw0zwprxlb";
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
