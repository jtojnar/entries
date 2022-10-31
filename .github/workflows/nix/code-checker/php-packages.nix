{composerEnv, fetchurl, fetchgit ? null, fetchhg ? null, fetchsvn ? null, noDev ? false}:

let
  packages = {
    "latte/latte" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "latte-latte-aad059390316d33c4c81a09703117303ce57f06c";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/latte/zipball/aad059390316d33c4c81a09703117303ce57f06c";
          sha256 = "0dkcnysfgr558hmi7mdy3irbxpi7jvz5mvmp7468k3v0qx1yv1n1";
        };
      };
    };
    "nette/application" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-application-a831a22c8291638624b39a673d40935c854371e3";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/application/zipball/a831a22c8291638624b39a673d40935c854371e3";
          sha256 = "1amk1id4j1rwid9i29j17kv109zbpnag4d05rszg5ixnk2msmrwv";
        };
      };
    };
    "nette/caching" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-caching-e1e38105956bb631e2295ef7a2fdef83485238e9";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/caching/zipball/e1e38105956bb631e2295ef7a2fdef83485238e9";
          sha256 = "0md5qyrzr9shzbvcj7s16446l1ww2h3376ijpqlfslwmqczww5n0";
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
        name = "nette-command-line-7027cbee2d283b5d482d11350dbb5399cc33b745";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/command-line/zipball/7027cbee2d283b5d482d11350dbb5399cc33b745";
          sha256 = "178mamjz1kv6kpdpdzwss96sgkflzi9byv9ahhw80vhgrlldb1fk";
        };
      };
    };
    "nette/component-model" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-component-model-20a39df12009029c7e425bc5e0439ee4ab5304af";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/component-model/zipball/20a39df12009029c7e425bc5e0439ee4ab5304af";
          sha256 = "1nj6zz0an0qkzr2f1f10kvc4cryfx7bai7wgkan0p2d0pmqwpy11";
        };
      };
    };
    "nette/finder" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-finder-4a1236db9067d86a75c3dcc0d9c2aced17f9bde8";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/finder/zipball/4a1236db9067d86a75c3dcc0d9c2aced17f9bde8";
          sha256 = "1313s20mbl640swl0ii512fab9p4qxr6v5vzyrfvj9r28lraq6ic";
        };
      };
    };
    "nette/forms" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-forms-fe2109ce8b77846a5f664bc412c7cf3008f63074";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/forms/zipball/fe2109ce8b77846a5f664bc412c7cf3008f63074";
          sha256 = "17w26wc4768snv021j9wi77j2k5wl26cjqbn8vdjdnvg04n3rf9a";
        };
      };
    };
    "nette/http" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-http-65bfe68f9c611e7cd1935a5f794a560c52e4614f";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/http/zipball/65bfe68f9c611e7cd1935a5f794a560c52e4614f";
          sha256 = "1cmb7500miw3s9sirl4pmzvkazznk47asjh1d1fqjyxw0bavwlb9";
        };
      };
    };
    "nette/neon" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-neon-22e384da162fab42961d48eb06c06d3ad0c11b95";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/neon/zipball/22e384da162fab42961d48eb06c06d3ad0c11b95";
          sha256 = "1359dbm6iqwqm4dygaqg7hh80yz8yhxlw7zwrs488n5ndavd3gzc";
        };
      };
    };
    "nette/routing" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-routing-5e02bdde257029db0223d3291c281d913abd587f";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/routing/zipball/5e02bdde257029db0223d3291c281d913abd587f";
          sha256 = "1brd0xghbfdig8dsdkqv0cb906g60gpwkwwmbgdj5rhyw8kamjzr";
        };
      };
    };
    "nette/utils" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-utils-02a54c4c872b99e4ec05c4aec54b5a06eb0f6368";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/utils/zipball/02a54c4c872b99e4ec05c4aec54b5a06eb0f6368";
          sha256 = "17kj603xalq0il1ss8zgylckjr9r2zlglk7gylpa7v9iddmfbw5p";
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
