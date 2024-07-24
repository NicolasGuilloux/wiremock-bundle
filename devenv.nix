{
  pkgs,
  lib,
  config,
  inputs,
  ...
}:

let wrapCmd = cmd: "${cmd} \"$@\"";
in
{
  name= "Wiremock Bundle";

  # PHP
  languages.php = {
    enable = true;
    extensions = [ "pcov" ];
    fpm.phpOptions = ''
      date.timezone = "Europe/Paris"
    '';
  };

  # WireMock
  services.wiremock.enable = true;
  services.wiremock.disableBanner = true;

  # https://devenv.sh/packages/
  packages = with pkgs; [ git ];

  # https://devenv.sh/tests/
  enterTest = ''
    echo "----------------"
    echo "PHPUnit Coverage"
    echo "----------------";
    phpunit-coverage

    echo "------------------"
    echo "PHPUnit End to End"
    echo "------------------";
    phpunit --group=end_to_end
  '';

  # https://devenv.sh/languages/
  enterShell = ''
    echo "-------------------------------"
    echo "Wiremock Bundle dev environment"
    echo "-------------------------------"
  '';

  # Scripts
  scripts.console.exec = wrapCmd "tests/Resources/Kernel/bin/console";
  scripts.phpunit.exec = wrapCmd "vendor/bin/phpunit";
  scripts.php-cs-fixer.exec = wrapCmd "vendor/bin/php-cs-fixer -vvv";
  scripts.phpinsights.exec = wrapCmd "vendor/bin/phpinsights";
  scripts.phpunit-coverage.exec = wrapCmd ''
    phpunit \
        --colors=never \
        --coverage-text \
        --log-junit reports/junit.xml \
        --coverage-html reports/coverage \
        --coverage-clover reports/clover.xml \
    '';

  scripts.dump-versions.exec = ''
    php -v && composer V
  '';

  # Pre Commit Hooks
  pre-commit.hooks.php-cs-fixer.enable = true;
  pre-commit.hooks.php-cs-fixer.settings.binPath = "vendor/bin/php-cs-fixer";
  pre-commit.hooks.php-cs-fixer.pass_filenames = false;

  pre-commit.hooks.phpinsights = {
    enable = true;
    name = "PHPInsights";
    entry = "phpinsights analyse";
    types = [ "php" ];
    pass_filenames = false;
  };

  pre-commit.hooks.phpunit = {
    enable = true;
    name = "PHPUnit";
    entry = "phpunit";
    types = [ "php" ];
    pass_filenames = false;
  };

  # Misc
  dotenv.disableHint = true;
}
