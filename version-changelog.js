#! /usr/bin/env node

var readline = require('readline');
var fs = require('fs');
var childProcess = require('child_process');

var rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

var pluginName = process.argv[2];
var pluginSlug = pluginName.substr(3);
var mainPHPFile = pluginName + '/' + pluginName + '.php';
var dbPHPFile = pluginName + '/' + pluginName + '-db.php';
var readmeFile = pluginName + '/readme.txt';
var generatedPOTFile = pluginName + '.pot';
var targetPOTFile = pluginName + '/lang/' + pluginSlug + '.pot';
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
  var pluginMain = fs.readFileSync(mainPHPFile, 'utf8');
  var indexStart = pluginMain.indexOf('Version: ') + 'Version: '.length;
  var indexEnd = pluginMain.indexOf('\n', indexStart);
  pluginMain = pluginMain.substring(0, indexStart) + version + pluginMain.substring(indexEnd);
  fs.writeFileSync(mainPHPFile, pluginMain);

  // Update version in the plugin's DB file.
  var pluginMain = fs.readFileSync(dbPHPFile, 'utf8');
  var indexStart = pluginMain.indexOf('const VERSION = \'') + 'const VERSION = \''.length;
  var indexEnd = pluginMain.indexOf('\'', indexStart);
  pluginMain = pluginMain.substring(0, indexStart) + version + pluginMain.substring(indexEnd);
  fs.writeFileSync(dbPHPFile, pluginMain);

  // Update version in the package.json file.
  var packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
  packageJson.version = version;
  fs.writeFileSync('package.json', JSON.stringify(packageJson, null, 2) + '\n');

  // Update readme.txt to add changelog.
  var readmeTxt = fs.readFileSync(readmeFile, 'utf8');
  var indexChangelog = readmeTxt.indexOf('== Changelog ==') + '== Changelog =='.length + 1;
  readmeTxt = readmeTxt.substring(0, indexChangelog) + '= ' + version + ' =\n' + changelog.join('\n') + '\n\n' + readmeTxt.substring(indexChangelog);
  fs.writeFileSync(readmeFile, readmeTxt);

  commitChanges();
}

function generatePOT() {
  childProcess.execSync('php tools/wordpress-repo/tools/i18n/makepot.php wp-plugin ' + pluginName);
  childProcess.execSync('mv ' + generatedPOTFile + ' ' + targetPOTFile);
}

function commitChanges() {
  var files = [
    'package.json',
    targetPOTFile,
    readmeFile,
    mainPHPFile,
    dbPHPFile,
  ].join(' ');

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
