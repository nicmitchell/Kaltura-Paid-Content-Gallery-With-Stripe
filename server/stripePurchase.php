<?php
require_once('./stripe-php/lib/Stripe.php');
// Set your secret key: remember to change this to your live secret key in production
// See your keys here https://dashboard.stripe.com/account
// if(isset($_POST['tokenppt'])){
  Stripe::setApiKey("sk_test_NiowSWa0qgCh0guA1wCk1nJp");

  // Get the credit card details submitted by the form
  $token = $_POST['token'];
  $amount = $_POST['amount'];
  $entryId = $_POST['entryId'];

  // Create the charge on Stripe's servers - this will charge the user's card
  try {
  $charge = Stripe_Charge::create(
    array(
      "amount" => $amount,
      "currency" => "usd",
      "card" => $token,
      "description" => "PPV Example")
    );
  } catch(Stripe_CardError $e) {
    // The card has been declined
  }
  // use the bill function
  // pptransact.bill()

  // save Kaltura metadata
  // savePurchase(entryId);

  // generate KS for paid video
  // $ks = checkAccess(entryId);

  // echo 'charged';
  // var_dump( $_POST );
  // echo $charge;
  echo $entryId;
  // echo 'success';
  // return $entryId;
// }

// $returnObj['paymentStatus'] = $response["PAYMENTINFO_0_PAYMENTSTATUS"];
//       $returnObj['itemId'] = $itemId;
//       $returnObj['userId'] = $userId;
//             recordPayment($returnObj);