<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 François MAURIN <framaurin@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/setup.php
 * \ingroup scierie
 * \brief   scierie setup page.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/scierie.lib.php';

// Translations
$langs->load("scierie@scierie");

// Access control
if (! $user->admin) accessforbidden();

// Récupérations
$consts=GETPOST('const','array');
$action=GETPOST('action','alpha');

// Update
if (! empty($consts) && $action == 'update')
{
	$nbmodified=0;
	foreach($consts as $const)
	{
		if (dolibarr_set_const($db, $const["name"], $const["value"], $const["type"], 1, $const["note"], $conf->entity) >= 0)
		{
			$nbmodified++;
		}
		else
		{
			dol_print_error($db);
		}
	}
	if ($nbmodified > 0) setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	$action='';
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


/*
 * View
 */

$page_name = "ScierieSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
print load_fiche_titre("Réglages du module Scierie",'','title_setup');

print "<b>Bonjour ma cannette</b> <br>\n Page de réglage du module Scierie<br>\n";
print "<br>\n";

print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug)?'?debug=1':'').'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" id="action" name="action" value="update">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Comment").'</td>';
print "</tr>\n";

// Show constants
$sql = "SELECT";
$sql.= " rowid";
$sql.= ", ".$db->decrypt('name')." as name";
$sql.= ", ".$db->decrypt('value')." as value";
$sql.= ", type";
$sql.= ", note";
$sql.= " FROM ".MAIN_DB_PREFIX."const";
$sql.= " WHERE entity IN (".$user->entity.",".$conf->entity.")";
$sql.= " AND visible = 1";	
$sql.= " AND name LIKE 'SCIERIE%'";		
if (GETPOST('name')) $sql.=natural_search("name", GETPOST('name'));
$sql.= " ORDER BY name ASC";

dol_syslog("Const::listConstant", LOG_DEBUG);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	$var=false;

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		

		print "\n";

		print '<tr class="oddeven"><td>'.$obj->name.'</td>'."\n";

		// Value
		print '<td>';
		print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->name.'">';
		print '<input type="hidden" name="const['.$i.'][type]" value="'.$obj->type.'">';
		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="30" name="const['.$i.'][value]" value="'.htmlspecialchars($obj->value).'">';
		print '</td>';

		// Note
		print '<td>';
		print '<input type="text" id="note_'.$i.'" class="flat inputforupdate" size="40" name="const['.$i.'][note]" value="'.htmlspecialchars($obj->note,1).'">';
		print '</td>';

		print "</td></tr>\n";

		print "\n";
		$i++;
	}
}

print '</table>';
print '</div>';
print '<br>';
print '<div id="updateconst" align="right">';
print '<input type="submit" name="update" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';
print "</form>\n";

// Page end
dol_fiche_end();
llxFooter();
$db->close();
