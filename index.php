<?php
/**
 * @file
 * Front controller.
 */

require_once 'config.inc.php';
require_once 'util.inc.php';
require_once 'AWSSDKforPHP/sdk.class.php';

$menu_item_default = 'status';
$menu_items = array(
  'status' => array(
    'label' => 'Status',
    'desc' => 'Overview of configuration and status',
  ),
  'show' => array(
    'label' => 'Show',
    'desc' => 'Show all watermarked images',
  ),
  'upload' => array(
    'label' => 'Upload',
    'desc' => 'Upload files to be watermarked',
  ),
  'process' => array(
    'label' => 'Process',
    'desc' => 'Add watermarks to images',
  ),
);

// Determine the current menu item.
$menu_current = $menu_item_default;
// If there is a query for a specific menu item and that menu item exists...
if (isset($_REQUEST['q']) && array_key_exists($_REQUEST['q'], $menu_items)) {
  $menu_current = $_REQUEST['q'];
}
?>
<!DOCTYPE html>
<html class="no-js">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Up and Running with Amazon Web Services</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width">

        <link rel="stylesheet" href="css/bootstrap.min.css">
        <style>
            body {
                padding-top: 60px;
                padding-bottom: 40px;
            }
        </style>
        <link rel="stylesheet" href="css/bootstrap-responsive.min.css">
        <link rel="stylesheet" href="css/main.css">

        <script src="js/vendor/modernizr-2.6.2-respond-1.1.0.min.js"></script>
    </head>
    <body>

        <!-- This code is taken from http://twitter.github.com/bootstrap/examples/hero.html -->

        <div class="navbar navbar-inverse navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                    <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </a>
                    <a class="brand" href="?q=<?php echo $menu_item_default; ?>">Up and Running with Amazon Web Services</a>
                    <div class="nav-collapse collapse">
                        <ul class="nav">
                          <?php
                          foreach ($menu_items as $item => $item_data) {
                            echo '<li' . ($item == $menu_current ? ' class="active"' : '') . '>';
                            echo '<a href="?q=' . $item . '" title="' . $item_data['desc'] . '">' . $item_data['label'] . '</a>';
                            echo '</li>';
                          }
                          ?>
                        </ul>
                    </div><!--/.nav-collapse -->
                </div>
            </div>
        </div>

        <div class="container">

            <!-- Main hero unit for a primary marketing message or call to action -->
            <div class="hero-unit">
              <?php
              echo '<h1>' . $menu_items[$menu_current]['label'] . '</h1>';
              echo '<p>' . $menu_items[$menu_current]['desc'] . '</p>';
              ?>
            </div>

            <?php
              include_once 'pages/' . $menu_current . '.php';
            ?>

            <hr>

            <footer>
                <p>&copy; lynda.com 2013</p>
            </footer>

        </div> <!-- /container -->

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.8.3.min.js"><\/script>')</script>
        <script src="js/vendor/bootstrap.min.js"></script>
        <script src="js/main.js"></script>
    </body>
</html>
