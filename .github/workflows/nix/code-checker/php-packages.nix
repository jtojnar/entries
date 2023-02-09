{composerEnv, fetchurl, fetchgit ? null, fetchhg ? null, fetchsvn ? null, noDev ? false}:

let
  packages = {
    "latte/latte" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "latte-latte-1ccb0add4ddc5e8b5db3b82a145fa9ff2d9d6f8f";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/latte/zipball/1ccb0add4ddc5e8b5db3b82a145fa9ff2d9d6f8f";
          sha256 = "1lpaig9mydzkikwl1xv61832zmivivp2pjdlkn3cb3qyfqrgv9y4";
        };
      };
    };
    "nette/application" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-application-9c31b24407623437c1e1345cc2bd4e210b290135";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/application/zipball/9c31b24407623437c1e1345cc2bd4e210b290135";
          sha256 = "0hzqycxmyicb1byns23y07sj23d0w9phcjfd8cvvx2yay3cid24z";
        };
      };
    };
    "nette/caching" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-caching-ceb814d7f0a2bb4eb5afbe908467801001187745";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/caching/zipball/ceb814d7f0a2bb4eb5afbe908467801001187745";
          sha256 = "1xg66l4crbv1svx00yclglc3byqpxl91dzw3qqph8lnhhi0xpixi";
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
        name = "nette-command-line-ff1ba481afa981a7e5a7c5e75c7f55668d471c1e";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/command-line/zipball/ff1ba481afa981a7e5a7c5e75c7f55668d471c1e";
          sha256 = "0znsq9yas532khqz9p8w5q8q5q7xx9gfhg24zz8g36w2p5fsnx8r";
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
        name = "nette-forms-12b4c12e9d65a4c97e10a37cee88fdd14db780b7";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/forms/zipball/12b4c12e9d65a4c97e10a37cee88fdd14db780b7";
          sha256 = "1a8ncwym7mclmv6via46xrrh3x1gqf2ppqrzvd6hinr4f2xayzcd";
        };
      };
    };
    "nette/http" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-http-0e16cd4f911665679b96bf569318a0dc7f087eda";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/http/zipball/0e16cd4f911665679b96bf569318a0dc7f087eda";
          sha256 = "1him993dzwjfcprsmanyiyrbsczwl05rar44qw8n0ibdvfnvsgak";
        };
      };
    };
    "nette/neon" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-neon-372d945c156ee7f35c953339fb164538339e6283";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/neon/zipball/372d945c156ee7f35c953339fb164538339e6283";
          sha256 = "12vcpjp1mcg2n31s9k2clfv99gqqg9djxwj0hrdhl6walr2svxc5";
        };
      };
    };
    "nette/routing" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-routing-eaefe6375303799366f3e43977daaf33f5f89b95";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/routing/zipball/eaefe6375303799366f3e43977daaf33f5f89b95";
          sha256 = "0agqgwd65yh0rp3vf565px4avbqfzrkj16r0yb9m0nc4zwhbdhyw";
        };
      };
    };
    "nette/utils" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-utils-c91bac3470c34b2ecd5400f6e6fdf0b64a836a5c";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/utils/zipball/c91bac3470c34b2ecd5400f6e6fdf0b64a836a5c";
          sha256 = "0bnhb22m74xx5hn6p83jxpcrm2s6h1rpw33c0ip9i50b1nflm1f5";
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
