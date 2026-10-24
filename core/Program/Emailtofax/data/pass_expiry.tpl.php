<?php

$template = array();
$template['subject'] = "Password expiring soon!";
$template['body'] = <<<EOS

<table style="width:100%; max-width:600px; margin:0 auto; border-collapse:collapse; font-family:Arial, sans-serif; color:#333333;">
  <tr>
    <td style="background-color:#ffffff; padding:40px; border:1px solid #eeeeee;">
      <h2 style="color:#333333; text-align:center;">Password expiring soon!</h2>
      <p style="font-size:16px; line-height:24px; color:#666666; text-align:center;">
        Please take a moment to update your password using the following steps to help keep your account secure:
      </p>
      <div style="font-size:16px; line-height:24px; color:#666666;">
        1. Login to ICTFAX.<br>
        2. Go to the header in the right corner and click on your user icon.<br>
        3. You will see the "Change Password" button. Click on that.<br>
        4. Follow the instructions to update your password.
      </div>
    </td>
  </tr>
  <tr>
    <td style="background-color:#f5f5f5; padding:20px; text-align:center;">
      <p style="font-size:12px; color:#999999; margin:0;">ICTCore is developed by <a href="http://ictinnovations.com/" style="color:#ff6f61; text-decoration:none;">ICT Innovations</a></p>
    </td>
  </tr>
</table>

EOS;
?>