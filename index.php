<?php
error_reporting(E_ALL | E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

spl_autoload_register(function ($class_name) {
    require_once $class_name . '.php';
});

require "../library/php/dbconnect.php";
require "../library/php/library.php";

function primeFactorization($number)
{
    $primeFactors = array();
    $isPrime = true;
    $f = 2;
    while ($number > 1) {
        $count = 0;
        while ($number % $f == 0) {
            $count++;
            $number /= $f;
        }
        if ($count > 0) {
            $isPrime = false;
            $primeFactors[$f] = $count;
        }
        $f++;
    }
    if ($isPrime)
        $primeFactors[$number] = 1;
    return $primeFactors;
}

function printPrimeFactorization($primeFactors)
{
    $strpf = "";
    foreach ($primeFactors as $prime => $count) {
        $strpf .= sprintf("%d<sup>%d</sup> ", $prime, $count);
    }
    return trim($strpf);
}

function listOfFactors($number)
{
    $list = array();
    $uplimit = (int)sqrt($number);
    for ($f = 1; $f <= $uplimit; $f++) {
        if ($number % $f === 0) {
            $list[] = $f;
            $list[] = $number / $f;
        }
    }
    sort($list);
    return $list;
}

function printFactorPairs($factors)
{
    $table = <<<EOT
<table id="factortable" class="table table-sm table-striped">
   <thead>
    <tr class="header">
        <th scope="col" id="col1">#</th>
        <th scope="col" id="col2">Factor Pair</th>
        <th scope="col" id="col3">Sum</th>
        <th scope="col" id="col4">Diff</th>
      </tr>
    </thead>    
    <tbody>
EOT;
    $pairColSize = (int)ceil(log10($factors[count($factors) - 1]));
    $count = 1;
    for ($first = 0, $last = count($factors) - 1; $first < $last; $first++, $last--) {
        $firstFactor = $factors[$first];
        $lastFactor = $factors[$last];
        $pair = sprintf("%${pairColSize}d &times; %${pairColSize}d", $firstFactor, $lastFactor);
        $sum = $firstFactor + $lastFactor;
        $diff = $lastFactor - $firstFactor;
        $rowCode = <<<EOT
    <tr>
      <th scope="row">$count</th>
      <td>$pair</td>
      <td>&plusmn;$sum</td>
      <td>&plusmn;$diff</td>
    </tr>
EOT;
        $count++;
        $table .= $rowCode;
    }
    $table .= <<<EOT
    </tbody>
    </table>
EOT;
    return $table;
}

function printListOfFactors($factors)
{
    return implode(", ", $factors);
}

function sharingURL($number)
{
    $url = $_SERVER["SERVER_PORT"] == 443 ? "https://" : "http://";
    $url .= $_SERVER["SERVER_NAME"];
    $url .= extractPath($_SERVER["PHP_SELF"]);
    $url .= "?number=" . $number;
    return $url;
}

function logAccess($number)
{
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->beginTransaction();
        $sql = 'INSERT INTO factoring(number, ipaddr) VALUES(:number, :ipaddr)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['number' => $number, 'ipaddr' => getUserIP()]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo 'Connection failed: ' . $e->getMessage();
    }
}

$title = "Number Factoring!";
$current = "factoring";
$errMsg = '';
$success = false;
$number = '';
if (isset($_REQUEST["number"])) :
    $number = trim($_REQUEST["number"]);
    $tmpnumber = test_input($number);
    if ($number !== $tmpnumber) :
        $errMsg = "Hacking Attempt Detected";
    else :
        if (!is_numeric($number) || ($number = (int)$number) <= 0 || $number > 1000000000) :
            $errMsg = "Please use integers greater than zero and less than 1,000,000,000";
        else :
            $primeFactors = primeFactorization($number);
            $strpf = printPrimeFactorization($primeFactors);
            $listOfFactors = listOfFactors($number);
            $strListOfFactors = printListOfFactors($listOfFactors);
            $strFactorPairs = printFactorPairs($listOfFactors);
            $strURL = sharingURL($number);
            logAccess($number);
            $success = true;
        endif;
    endif;
endif;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo $title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="Antonio C. Silvestri">
    <meta name="description" content="Number Factoring! This system will accept a number and outputs its Prime Factorization, its list of factors, and its Factor Pairs, Sums, and Differences. This data can be extremely useful when trying to factor trinomials.">
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styles.css">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:site" content="@bytecodeman">
    <meta name="twitter:title" content="<?php echo $title; ?>">
    <meta name="twitter:description" content="Number Factoring! This system will accept a number and outputs its Prime Factorization, its list of factors, and its Factor Pairs, Sums, and Differences. This data can be extremely useful when trying to factor trinomials.">
    <meta name="twitter:image" content="https://cs.stcc.edu/specialapps/factoring/img/factoring.jpg">

    <meta property="og:url" content="https://cs.stcc.edu/specialapps/factoring/">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="Number Factoring! This system will accept a number and outputs its Prime Factorization, its list of factors, and its Factor Pairs, Sums, and Differences. This data can be extremely useful when trying to factor trinomials.">
    <meta property="og:image" content="https://cs.stcc.edu/specialapps/factoring/img/factoring.jpg">

    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({
            google_ad_client: "ca-pub-9626577709396562",
            enable_page_level_ads: true
        });
    </script>
</head>

<body>
    <?php include "../library/php/navbar.php"; ?>
    <div class="container">
        <div class="jumbotron">
            <div class="row">
                <div class="col-lg-8">
                    <h1><?php echo $title; ?></h1>
                    <div>
                        <p>This system will accept a number greater than 0 and outputs the following info:</p>
                        <ul>
                            <li>Prime Factorization</li>
                            <li>List of Factors</li>
                            <li>Factor Pairs, Sums, and Differences</li>
                        </ul>
                        <p>This data can be extremely useful when trying to factor trinomials.</p>
                        <p class="d-print-none"><a href="#" data-toggle="modal" data-target="#myModal">About <?php echo $title; ?></a></p>
                        <p><a href="https://github.com/bytecodeman/factoring" target="_blank" rel="noopener noreferrer">Source Code</a></p>
                    </div>
                </div>
                <div class="col-lg-4 d-print-none">
                    <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9626577709396562" data-ad-slot="7064413444" data-ad-format="auto"></ins>
                    <script>
                        (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php if (!empty($errMsg)) : ?>
                    <div id="errMsg" class="font-weight-bold h4 text-danger">
                        <?php echo $errMsg; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success) : ?>
                    <h1 class="font-weight-bold text-success">Factorization Report of <?php echo $number; ?></h1>
                    <fieldset class="factoring">
                        <legend class="text-success">Prime Factorization</legend>
                        <div><?php echo $strpf; ?></div>
                    </fieldset>
                    <fieldset class="factoring">
                        <legend class="text-success">List of Factors</legend>
                        <div><?php echo $strListOfFactors; ?></div>
                    </fieldset>
                    <fieldset class="factoring">
                        <legend class="text-success">Factor Pairs, Sums and Differences</legend>
                        <div class="table-responsive"><?php echo $strFactorPairs; ?></div>
                    </fieldset>
                    <fieldset class="factoring">
                        <legend class="text-success">Share URL
                            <div id="copyToClipboard">
                                <a tabindex="0" id="copytoclip" data-trigger="focus" data-clipboard-target="#sharingURL" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Copied!">
                                    <img src="img/clippy.svg" alt="Copy to Clipboard" title="Copy to Clipboard">
                                </a>
                            </div>
                        </legend>
                        <div id="sharingURL"><?php echo $strURL; ?></div>
                    </fieldset>
                <?php endif; ?>
                <form id="factorform" method="post" action="<?php echo htmlspecialchars(extractPath($_SERVER["PHP_SELF"])); ?>">
                    <div class="form-group">
                        <label for="number">Find the Factors of:</label>
                        <input type="number" id="number" name="number" min="1" class="form-control form-control-lg" placeholder="Enter a Number" value="<?php echo $number; ?>" required>
                    </div>
                    <button type="submit" id="submit" name="submit" class="btn btn-primary btn-lg d-print-none">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <?php
    require "../library/php/about.php";
    ?>

    <script src="//code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="//stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5a576c39d176f4a6"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js"></script>
    <script>
        $(function() {
            $('[data-toggle="popover"]').popover();
            new Clipboard("#copytoclip");

            $("#factorform").submit(function() {
                $("#submit").html('Please Wait <i class="fas fa-spinner fa-spin fa-lg ml-3"></i>').attr("disabled", "disabled");
                return true;
            });

        });
    </script>
</body>

</html>