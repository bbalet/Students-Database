<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$category = new datamodel\DataDisplayCategory("Information Session");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("InformationSession", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Name"));
$display->addDataDisplay(new CalendarEventDateDataDisplay("Date", "date"));
$display->addDataDisplay(new datamodel\CalculationDataDisplay("Expected", "`number_boys_expected`+`number_girls_expected`"));
$display->addDataDisplay(new datamodel\CalculationDataDisplay("Attendees", "`number_boys_real`+`number_girls_real`"));
$display->addDataDisplay(new datamodel\CountLinksDataDisplay("Applicants", "Applicant", "information_session"));
$model->addTableDataDisplay($display);
?>