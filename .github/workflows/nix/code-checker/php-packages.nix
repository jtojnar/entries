{composerEnv, fetchurl, fetchgit ? null, fetchhg ? null, fetchsvn ? null, noDev ? false}:

let
  packages = {
    "latte/latte" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "latte-latte-fc94bd63fe995b40cb219109026e76f281c709c2";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/latte/zipball/fc94bd63fe995b40cb219109026e76f281c709c2";
          sha256 = "11hsh3j6ah9r3l3fmh9zzlghcxhpg7whl4i6x6rwxp98vz477qcp";
        };
      };
    };
    "nette/application" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-application-783ad6fc6444f63314175131885c04b3dd0291dd";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/application/zipball/783ad6fc6444f63314175131885c04b3dd0291dd";
          sha256 = "1lw8cnd8pb8c4mns3ryl1xlxx9frvj6dxg2v2lzah3dyx7v9p320";
        };
      };
    };
    "nette/caching" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-caching-b9ecbf920f240bd1ab14900d9a77876924ad7fb4";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/caching/zipball/b9ecbf920f240bd1ab14900d9a77876924ad7fb4";
          sha256 = "1diissvhm2v0wzgc4v5gggsafrl7d71irdpdqg2hhm1k2rqxw3ky";
        };
      };
    };
    "nette/code-checker" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-code-checker-faae7ac04dcdebf319f1f69c128ff8015f930d11";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/code-checker/zipball/faae7ac04dcdebf319f1f69c128ff8015f930d11";
          sha256 = "1yi1aay2iikiyr7bpp0mwjn2a8ac7rnrmfy7sbqr5qgk6mv9ymwn";
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
        name = "nette-component-model-66409cf5507c77edb46ffa88cf6a92ff58395601";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/component-model/zipball/66409cf5507c77edb46ffa88cf6a92ff58395601";
          sha256 = "0pf2xkwfsy6amcbl8mdks7awgnqlmvc3adk8hz9s2lfjmgh4zfc2";
        };
      };
    };
    "nette/finder" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-finder-4ad2c298eb8c687dd0e74ae84206a4186eeaed50";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/finder/zipball/4ad2c298eb8c687dd0e74ae84206a4186eeaed50";
          sha256 = "1bsgpmlk3mvyv3x5i6q4f6mrd2dpcjjb691z6gax71mim2ip7alb";
        };
      };
    };
    "nette/forms" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-forms-ecb5f7b8c82585c5fc4698ccb6815542fe6b2db4";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/forms/zipball/ecb5f7b8c82585c5fc4698ccb6815542fe6b2db4";
          sha256 = "18cms2s0shwfjfz8r99r6dpx9pn6fs9vdkql5i2dcra313yagmkl";
        };
      };
    };
    "nette/http" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-http-e4d8d360c66c8af9512ca13ab629d312af2b3ce3";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/http/zipball/e4d8d360c66c8af9512ca13ab629d312af2b3ce3";
          sha256 = "07wlpzfdfqp2wkmz84avnfqkbfxl45cgxxw27yaxq1naca9zpyxr";
        };
      };
    };
    "nette/neon" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-neon-a5b3a60833d2ef55283a82d0c30b45d136b29e75";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/neon/zipball/a5b3a60833d2ef55283a82d0c30b45d136b29e75";
          sha256 = "0hx4vqg0khfrg5ww6dzw3dnwqnnnfzq7rapl15xws22m5jkl2w21";
        };
      };
    };
    "nette/routing" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-routing-603c697f3df7ed214795d4e8e8c58fbf981232b1";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/routing/zipball/603c697f3df7ed214795d4e8e8c58fbf981232b1";
          sha256 = "04pz69hznr3gwfql5qnqds5ldgnvw8ywyl2rw1g83qdyg1rnvzry";
        };
      };
    };
    "nette/utils" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nette-utils-c09937fbb24987b2a41c6022ebe84f4f1b8eec0f";
        src = fetchurl {
          url = "https://api.github.com/repos/nette/utils/zipball/c09937fbb24987b2a41c6022ebe84f4f1b8eec0f";
          sha256 = "1ik3xfqzwrxdbrjpxlfpkk83k1l98xjvyc3hsc6472dpkmm96n0l";
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
