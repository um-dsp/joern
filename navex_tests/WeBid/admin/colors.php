<?php
/***************************************************************************
 *   copyright				: (C) 2008 WeBid
 *   site					: http://sourceforge.net/projects/simpleauction
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

require('../includes/config.inc.php');
include "loggedin.inc.php";
?>
<html>
<head>
<link rel='stylesheet' type='text/css' href='style.css' />
<script language=Javascript>
<!--
function ChooseColor(val,what,T)
{
    what.value = val;
}
//-->
</script>
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#0066FF" vlink="#666666" alink="#000066" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td background="images/bac_barint.gif">
            <table width="100%" border="0" cellspacing="5" cellpadding="0">
                <tr>
                    <td width="30"><img src="images/i_gra.gif" width="21" height="19"></td>
                    <td class=white><?=$MSG_25_0009?>&nbsp;&gt;&gt;&nbsp;<?=$MSG_5002?></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" valign="middle">&nbsp;</td>
    </tr>
    <tr>
        <td align="center" valign="middle">
            <table border=0 width=100% cellpadding=0 cellspacing=0 bgcolor="#FFFFFF">
                <tr>
                    <td align="center"> <br>
                        <form name=conf action=<?=basename($_SERVER[PHP_SELF])?> method=POST>
                            <table width="95%" border="0" cellspacing="0" cellpadding="1" bgcolor="#0083D7">
                                <tr>
                                    <td align=CENTER class=title><?php print $MSG_5002; ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width=100% cellspacing=1 cellpadding=2 align="CENTER" bgcolor="#ffffff">
                                            <tr>
                                            <td COLSPAN=2>
                                                <?=$MSG_30_0011?><br>
                                                <b><?=$MSG_30_0010?></b>(<CODE><?="themes/".$SETTINGS['theme']."/style.css"?></CODE>)
                                            </td>
                                            </tr>
                                            <tr valign="TOP">
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_586; ?></h3>
                                                    <?= $MSG_587; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=.container&from=colors.php&color=border><?=$MSG_30_0012?></A>
                                                </td>
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_30_0013; ?></h3>
                                                    <?= $MSG_30_0014; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=.titTable1&from=colors.php&color=tittable><?=$MSG_30_0015?></A>
                                                </td>
                                            </tr>
                                            <tr valign="TOP">
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_30_0016; ?></h3>
                                                    <?= $MSG_30_0017; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=.hg&from=colors.php&color=hg><?=$MSG_30_0018?></A>
                                                </td>
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_595; ?></h3>
                                                    <?= $MSG_30_0019; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=a:link&from=colors.php&color=a:link><?=$MSG_30_0020?></A>
                                                </td>
                                            </tr>
                                            <tr valign="TOP">
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_596; ?></h3>
                                                    <?= $MSG_30_0021; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=a:visited&from=colors.php&color=a:visited><?=$MSG_30_0022?></A>
                                                </td>
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_30_0023; ?></h3>
                                                    <?= $MSG_30_0024; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=body&from=colors.php&color=body><?=$MSG_30_0025?></A>
                                                </td>
                                            </tr>
                                            <tr valign="TOP">
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_30_0026; ?></h3>
                                                    <?= $MSG_30_0027; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=.container&from=colors.php&color=container><?=$MSG_30_0028?></A>
                                                </td>
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_30_0187; ?></h3>
                                                    <?= $MSG_30_0188; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=.navbar&from=colors.php&color=container><?=$MSG_30_0189?></A>
                                                </td>
                                            </tr>
                                            <tr valign="TOP">
                                                <td bgcolor="#eeeeee">
                                                    <h3><?= $MSG_30_0191; ?></h3>
                                                    <?= $MSG_30_0192; ?>
                                                    <p align=right><A HREF=../csseditor_.php?thestyle=themes/<?=$SETTINGS['theme']?>/style.css&sel=.titTable5&from=colors.php&color=tittable><?=$MSG_30_0193?></A>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
