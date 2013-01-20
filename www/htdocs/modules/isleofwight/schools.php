<?
include_once('modules/schools/ds.php');

class IsleOfWightSchoolsDataSource extends SchoolsDataSource
{
	static function getFilter($v)
	{
		return $v." school:localAuthority <http://statistics.data.gov.uk/id/local-education-authority/921> .";
	}
}
