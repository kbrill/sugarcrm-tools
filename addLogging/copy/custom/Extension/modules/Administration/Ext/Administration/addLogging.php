<?php
/*********************************************************************************
 * addLogging
 * Kenneth Brill (kbrill@sugarcrm.com)
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY KENNETH BRILL, KENNETH BRILL DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 *
 * @category   Custom Admin Menu
 * @package    addLogging
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2015-2016 SugarCRM
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    1.0
 * @link       http://www.sugarforge.org/reviews/?group_id=1300
 */
$admin_option_defs=array();
$admin_option_defs['Administration']['ADDLOGGING']= array('Administration','LBL_ADDLOGGING','LBL_ADDLOGGING_DESC','./index.php?module=Administration&action=addLogging');
$admin_group_header[]=array('LBL_ADDLOGGING','',false,$admin_option_defs);
