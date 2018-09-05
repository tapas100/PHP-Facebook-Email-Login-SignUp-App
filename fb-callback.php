<?php
session_start();
require_once __DIR__ . '/Facebook/autoload.php';
require_once 'dbconnect.php';

$fb = new Facebook\Facebook([
  'app_id' => '2219937414906598', // Replace {app-id} with your app id
  'app_secret' => 'd79a30fe3e26c2e3d11273ccc1f09f90',
  'default_graph_version' => 'v3.1',
  'persistant_data_handler' => 'session'
  ]);

$helper = $fb->getRedirectLoginHelper();

try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if (! isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Bad request';
  }
  exit;
}

// Logged in
echo '<h3>Access Token</h3>';
var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
$oAuth2Client = $fb->getOAuth2Client();

// Get the access token metadata from /debug_token
$tokenMetadata = $oAuth2Client->debugToken($accessToken);
echo '<h3>Metadata</h3>';
try {
  // Returns a `FacebookFacebookResponse` object
  $response = $fb->get(
    '/me?locale=en_US&fields=name,email,first_name',
    $accessToken->getValue()
  );
} catch(FacebookExceptionsFacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(FacebookExceptionsFacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}
$graphNode = $response->getGraphNode();
var_dump($graphNode['email']);

var_dump($tokenMetadata);

// Validation (these will throw FacebookSDKException's when they fail)
$tokenMetadata->validateAppId('2219937414906598'); // Replace {app-id} with your app id
// If you know the user ID this access token belongs to, you can validate it here
//$tokenMetadata->validateUserId('123');
$tokenMetadata->validateExpiration();

if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
    exit;
  }

  echo '<h3>Long-lived</h3>';
  var_dump($accessToken->getValue());
}

//$_SESSION['fb_access_token'] = (string) $accessToken;

 // check email exist or not
    $stmt = $conn->prepare("SELECT email FROM users WHERE email=?");
    $stmt->bind_param("s", $graphNode['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $count = $result->num_rows;
    var_dump($count);
if ($count == 0) { // if email is not found add user

        $stmts = $conn->prepare("INSERT INTO users(username,email,password) VALUES(?, ?, ?)");
        $stmts->bind_param("sss", $graphNode['first_name'], $graphNode['email'], $graphNode['email']);
        $res = $stmts->execute();//get result
        $stmts->close();

        $user_id = mysqli_insert_id($conn);
        var_dump($user_id);
        if ($user_id > 0) {
            $_SESSION['user'] = $user_id; // set session and redirect to index page
            if (isset($_SESSION['user'])) {
                print_r($_SESSION);
                header("Location: index.php");
                exit;
            }

        } else {
            $errTyp = "danger";
            $errMSG = "Something went wrong, try again";
        }

    } else {
       // $errTyp = "warning";
       // $errMSG = "Email is already used";
      $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email= ?");
      $stmt->bind_param("s", $graphNode['email']);
    /* execute query */
      $stmt->execute();
    //get result
      $res = $stmt->get_result();
      $stmt->close();
      var_dump($res);
      $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
      var_dump($row['id']);
      $count = $res->num_rows;
      $_SESSION['user'] = $row['id'];
      //var_dump($_SESSION);
      header("Location: index.php");
      exit;

    }


session_destroy();
// User is logged in with a long-lived access token.
// You can redirect them to a members-only page.
//header('Location: https://example.com/members.php');
?>
