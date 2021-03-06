<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
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

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
dol_include_once('dolifleet/class/rentalProposal.class.php');

if(empty($user->rights->dolifleet->rentalproposal->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('dolifleet@dolifleet');


$massaction = GETPOST('massaction', 'alpha');
$confirmmassaction = GETPOST('confirmmassaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$object = new dolifleetRentalProposal($db);

$hookmanager->initHooks(array('dolifleetrentalproposallist'));

if ($object->isextrafieldmanaged)
{
	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
}

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend')
{
	$massaction = '';
}

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha'))
{
	unset($_GET['Listview_dolifleetrentalproposal_search_fk_soc']);
}

if (empty($reshook))
{
	// do action from GETPOST ...
}


/*
 * View
 */

llxHeader('', $langs->trans('dolifleetRentalProposalList'), '', '');

//$type = GETPOST('type');
//if (empty($user->rights->dolifleetrentalproposal->all->read)) $type = 'mine';

// TODO ajouter les champs de son objet que l'on souhaite afficher
$keys = array_keys($object->fields);
$fieldList = 't.'.implode(', t.', $keys);
if (!empty($object->isextrafieldmanaged))
{
	$keys = array_keys($extralabels);
	if(!empty($keys)) {
		$fieldList .= ', et.' . implode(', et.', $keys);
	}
}

$sql = 'SELECT '.$fieldList;

// Add fields from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' t ';

if (!empty($object->isextrafieldmanaged))
{
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields et ON (et.fk_object = t.rowid)';
}

$sql.= ' WHERE 1=1';
//$sql.= ' AND t.entity IN ('.getEntity('dolifleetRentalProposal', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;

// Add where from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_dolifleetrentalproposal', 'GET');

$nbLine = GETPOST('limit');
if (empty($nbLine)) $nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

// List configuration
$listViewConfig = array(
	'view_type' => 'list' // default = [list], [raw], [chart]
	,'allow-fields-select' => true
	,'limit'=>array(
		'nbLine' => $nbLine
	)
	,'list' => array(
		'title' => $langs->trans('dolifleetRentalProposalList')
		,'image' => 'title_generic.png'
		,'picto_precedent' => '<'
		,'picto_suivant' => '>'
		,'noheader' => 0
		,'messageNothing' => $langs->trans('NodoliFleet')
		,'picto_search' => img_picto('', 'search.png', '', 0)
		,'param_url' => '&limit='.$nbLine
		//		,'massactions'=>array(
//			'yourmassactioncode'  => $langs->trans('YourMassActionLabel')
//		)
	)
	,'subQuery' => array()
	,'link' => array()
	,'type' => array(
		'date_creation' => 'date' // [datetime], [hour], [money], [number], [integer]
		,'tms' => 'date'
	)
	,'search' => array(
		'date_creation' => array('search_type' => 'calendars', 'allow_is_null' => true)
		,'ref' => array('search_type' => true, 'table' => 't', 'field' => 'ref')
//	,'tms' => array('search_type' => 'calendars', 'allow_is_null' => false)
//	,'label' => array('search_type' => true, 'table' => array('t', 't'), 'field' => array('label')) // input text de recherche sur plusieurs champs
		,'status' => array('search_type' => dolifleetRentalProposal::$TStatus, 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
		,'month' => array('search_type' => monthArray($langs))
		,'year' => array('search_type' => true, 'table' => 't', 'field', 'year')
		,'fk_soc' => array('search_type' => 'override', 'override' => $object->showInputField($object->fields['fk_soc'], 'fk_soc', GETPOST('Listview_dolifleetrentalproposal_search_fk_soc'),'','','Listview_dolifleetrentalproposal_search_'))
	)
	,'translate' => array()
	,'hide' => array(
		'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
	)
	,'title'=>array(
		'ref' => $langs->trans('Ref.')
//		,'label' => $langs->trans('Label')
//		,'date_creation' => $langs->trans('DateCre')
//		,'tms' => $langs->trans('DateMaj')
//		,'id'=>$langs->trans('Id')
		,'month' => $langs->trans('Month')
		,'year' => $langs->trans('Year')
		,'fk_soc'=> $langs->trans('ThirdParty')
		,'status'=> $langs->trans('Status')
	)
	,'eval'=>array(
		'ref' => '_getObjectNomUrl(\'@rowid@\')'
		,'fk_soc' => '_getObjectNomUrl(\'@val@\', "Societe")'
		,'month' => '_displayMonth(@val@)'
		,'status' => "_printStatus(@val@)"
//		,'fk_user' => '_getUserNomUrl(@val@)' // Si on a un fk_user dans notre requête
	)
);

$r = new Listview($db, 'dolifleetrentalproposal');

// Change view from hooks
$parameters=array(  'listViewConfig' => $listViewConfig);
$reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if ($reshook>0)
{
	$listViewConfig = $hookmanager->resArray;
}

echo $r->render($sql, $listViewConfig);

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');
$db->close();

/**
 * TODO remove if unused
 */
function _getObjectNomUrl($id, $classname = 'dolifleetRentalProposal')
{

	global $db;

	if ($classname == 'Societe') require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$o = new $classname($db);
	if ($classname == 'dolifleetRentalProposal') $res = $o->fetch($id, false, '');
	else $res = $o->fetch($id);
	if ($res > 0)
	{
		return $o->getNomUrl(1);
	}

	return '';
}

function _printStatus($fk_status)
{
	global $langs;

	return $langs->trans(dolifleetRentalProposal::$TStatus[$fk_status]);
}

function _displayMonth($monthNumber)
{
	global $langs, $TMonth;

	if (empty($TMonth)) $TMonth = monthArray($langs);

	return $TMonth[$monthNumber];

}

/**
 * TODO remove if unused
 */
function _getUserNomUrl($fk_user)
{
	global $db;

	$u = new User($db);
	if ($u->fetch($fk_user) > 0)
	{
		return $u->getNomUrl(1);
	}

	return '';
}
