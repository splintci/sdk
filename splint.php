<?php
/**
 * Splint Production Server Package and Dependency Manager.
 * (c) CynoBit 2019
 * Date Created: 22/2/2019 11:06 AM
 * Author: Francis Ilechukwu
 * Email:  francis.ilechukwu@cynobit.com
 */

// =============================================================================
// FIELDS - START
// =============================================================================

/**
 * [$version description]
 * @var string
 */
$version = "0.0.1";
/**
 * [$nl description]
 * @var string
 */
$nl = "<br/>";
/**
 * [$user_agent description]
 * @var string
 */
$user_agent = "Splint-Production-Client";
/**
 * [$app_folder description]
 * @var string
 */
$app_folder = "application";

// =============================================================================
// FIELDS - END
// =============================================================================

/**
 * [printLine description]
 * @param  [type] $str [description]
 * @return [type]      [description]
 */
function printLine($str=null, $color="black") {
  echo "<font color=\"$color\">$str</font>" . $GLOBALS["nl"];
  ob_flush();
  flush();
}
/**
 * [downloadPackage description]
 * @param  [type] $package   [description]
 * @param  [type] $integrity [description]
 * @return [type]            [description]
 */
function downloadPackage($package, $integrity) {
  $url = "http://localhost/splint.cynobit.com/index.php/SplintClient/downloadPackage";
  $params = array("identifier" => $package);
  $options = array(
    "http" => array(
    "header"  => "Content-type: application/x-www-form-urlencoded\r\nUser-Agent:"
    . $GLOBALS["user_agent"] . "\r\n",
    "method"  => "POST",
    "content" => http_build_query($params)
    )
  );
  $context  = stream_context_create($options);
  $result = file_get_contents($url, false, $context);
  if ($result === FALSE) die("Error requesting packages.");
  $package = str_replace("/", "#", $package);
  file_put_contents("$package.zip", $result);
  if (filesize("$package.zip") == 0) return 0;
  return hash_file("md5", "$package.zip") === $integrity;
}
/**
 * [installPackage description]
 * @param  [type] $package [description]
 * @return [type]          [description]
 */
function installPackage($package) {
  if (!is_dir($GLOBALS["app_folder"] . "/splints/$package"))
    mkdir($GLOBALS["app_folder"] . "/splints/$package", 755, true);
  $zip = new ZipArchive;
  if ($zip->open(str_replace("/", "#", $package) . ".zip") !== true)
    die("Could not open package $package.");
  $zip->extractTo($GLOBALS["app_folder"] . "/splints/$package");
  $zip->close();
}
/**
 * [installPackages description]
 * @param  [type] $splints [description]
 * @return [type]          [description]
 */
function installPackages($splints) {
  $to_install = array();
  $no_install = array();
  $header = "Content-type: application/x-www-form-urlencoded\r\nUser-Agent:
    Splint-Production-Client\r\n";
  //$url = "https://splint.cynobit.com/index.php/SplintClient/requestPackages";
  $url = "http://localhost/splint.cynobit.com/index.php/SplintClient/requestPackages";
  $params = array("identifiers" => implode(",", $splints));
  $options = array(
    "http" => array(
    "header"  => "Content-type: application/x-www-form-urlencoded\r\nUser-Agent:"
    . $GLOBALS["user_agent"] . "\r\n",
    "method"  => "POST",
    "content" => http_build_query($params)
    )
  );
  $context  = stream_context_create($options);
  $result = file_get_contents($url, false, $context);
  if ($result === FALSE) die("Error requesting packages.");
  $packages = json_decode($result)->data;
  if (count($packages) == 0) {
    printLine("The following packages do not exist.", "orange");
    foreach ($splints as $splint) {
      printLine($splint);
    }
  }
  foreach ($packages as $package) {
    if (in_array($package->identifier, $splints)) {
      $to_install[] = $package;
    } else {
      $no_install[] = $package;
    }
  }
  if (count($no_install) > 0) {
    printLine("The following packages do not exist.", "orange");
    foreach ($no_install as $package) {
      printLine($package);
    }
  }
  foreach ($to_install as $package) {
    printLine("Downloading package $package->identifier...");
    $response = downloadPackage($package->identifier, $package->integrity);
    if ($response === 0) die ("Package could not be downloaded.");
    if ($response === false) die ("Package lacks integrity, possible MITM Attack.");
    printLine("Done Downloading package $package->identifier.");
    printLine();
  }
  if (count($to_install) > 0) {
    printLine("The following packages will be installed:");
    foreach ($to_install as $package) {
      printLine("[*] $package->identifier");
    }
    printLine();
    foreach($to_install as $package) {
      printLine("Installing $package->identifier...");
      installPackage($package->identifier);
      printLine("Done installing $package->identifier.");
      printLine();
    }
  }
  return $to_install;
}
/**
 * [getDependencies description]
 * @param  [type] $packages [description]
 * @return [type]           [description]
 */
function getDependencies($packages) {
  $dependencies = array();
  foreach ($packages as $package) {
    $path = $GLOBALS["app_folder"] . "/splints/" . $package->identifier .
    "/splint.json";
    if (is_file($path)) {
      $descriptor = json_decode($path);
      //$descriptor->depends_on[] = "francis94c/ci-preferences";
      if (isset($descriptor->depends_on) && is_array($descriptor->depends_on)) {
        foreach ($descriptor->depends_on as $dependency) {
          preg_match("/(\w+)\/([a-zA-Z0-9_\-]+)/", $dependency, $matches);
          if ($matches[0] == $dependency) {
            printLine("Found dependency: $dependency.");
            printLine();
            $dependencies[] = $dependency;
          }
        }
      }
    } else {
      printLine("No descriptor file found for " . $package->identifier . ".",
      "orange");
      printLine("Skipping...");
      continue;
    }
  }
  return $dependencies;
}

printLine("&#8224; <a href=\"https://splint.cynobit.com\" target=\"_blank\">Splint</a> Production
Environment Package Manager v$version &#8224;", "teal");
printLine();
// A 'splint.json' file is required to execute this script further.
if (!is_file("splint.json")) die("Could not find a 'splint.json' file.");
$data = json_decode(file_get_contents("splint.json"));
if (!isset($data->install) || count($data->install) == 0) die("No packages found to install");
if (!is_dir(__DIR__ . "/$app_folder")) die("Application folder not found.");
$splints = $data->install;
$valid_packages = array();
foreach ($splints as $splint) {
  preg_match("/(\w+)\/([a-zA-Z0-9_\-]+)/", $splint, $matches);
  if ($matches[0] != $splint) die ("Bad splint package pattern '$splint'");
  $valid_packages[] = $splint;
}
printLine("Splint will download and install the following package(s):");
foreach ($valid_packages as $package) {
  printLine($package);
}
printLine();
$dependencies = getDependencies(installPackages($valid_packages));
while (count($dependencies) > 0) {
  $dependencies = getDependencies(installPackages($dependencies));
}
printLine();
printLine("Done Installing Packages. :-)", "green");
?>
