<!-- 
leave this in the same directory as wu_widget.php and just call it from
any pages you want to embed it in. You can delete this message. 
Remember to set $save_forecast in wu_widget.php to true.
-->

<!-- begin wu_widget -->
<div class="test">
  <table style="border-collapse: collapse; width: 140px; border: auto solid #2a0975;">
    <tr>
      <td style="text-align: left; padding: none; color: #bbb; background-color: #2a0975; text-align: center; box-shadow: 2px 2px 2px 2px #999;">
        <img style="display:block; margin-left:auto; margin-right:auto; width:127px;" src="wu_widget.gif">
        <marquee scrollamount="4"> <?php readfile('forecast.txt');?> </marquee> <!-- html 4 for the win -->
      </td>
    </tr>
  </table>
</div>
<!-- end wu_widget -->
