#! /usr/bin/env node

var readline = require('readline');
var fs = require('fs');
var childProcess = require('child_process');

var rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

var version;
var changelog = [];

rl.question('New version: ', answer => {
  version = answer;
  askChangelog();
});

function askChangelog() {
  rl.write('Changelog:\n');
  rl.setPrompt('');
  rl.prompt();

  rl.on('line', function(cmd) {
    if (!cmd) {
      rl.pause();
      writeFiles();
    }

    changelog.push(cmd);
  });
}

function writeFiles() {
  // Update version in the plugin's main file.
  var pluginMain = fs.readFileSync('wp-web-push/wp-web-push.php', 'utf8');
  var indexStart = pluginMain.indexOf('Version: ') + 'Version: '.length;
  var indexEnd = pluginMain.indexOf('\n', indexStart);
  pluginMain = pluginMain.substring(0, indexStart) + version + pluginMain.substring(indexEnd);
  fs.writeFileSync('wp-web-push/wp-web-push.php', pluginMain);

  // Update version in the package.json file.
  var packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
  packageJson.version = version;
  fs.writeFileSync('package.json', JSON.stringify(packageJson, null, 2) + '\n');

  // Update readme.txt to add changelog.
  var readmeTxt = fs.readFileSync('wp-web-push/readme.txt', 'utf8');
  var indexChangelog = readmeTxt.indexOf('== Changelog ==') + '== Changelog =='.length + 1;
  readmeTxt = readmeTxt.substring(0, indexChangelog) + '= ' + version + ' =\n' + changelog.join('\n') + '\n\n' + readmeTxt.substring(indexChangelog);
  fs.writeFileSync('wp-web-push/readme.txt', readmeTxt);

  commitChanges();
}

function generatePOT() {
  childProcess.execSync('php tools/wordpress-repo/tools/i18n/makepot.php wp-plugin wp-web-push');
  childProcess.execSync('mv wp-web-push.pot wp-web-push/lang/web-push.pot');
}

function commitChanges() {
  var files = 'package.json wp-web-push/lang/web-push.pot wp-web-push/readme.txt wp-web-push/wp-web-push.php';

  generatePOT();
  childProcess.execSync('git diff ' + files, { stdio: [ process.stdin, process.stdout, process.stderr ] });

  rl.resume();
  rl.question('\nAre you satisfied [y/N]: ', answer => {
    rl.close();

    if (answer === 'y' || answer === 'Y') {
      childProcess.execSync('git add ' + files);
      childProcess.execSync('git commit -m "Version ' + version + '."');
      childProcess.execSync('git tag ' + version);
    } else {
      childProcess.execSync('git checkout -- ' + files);
      // Exit with an error to make 'make' fail.
      process.exit(1);
    }
  });
}
