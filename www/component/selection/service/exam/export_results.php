<?php 
class service_exam_export_results extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() { echo "Export exam results into an Excel file, for analysis"; }
	public function inputDocumentation() { echo "progress_id, all"; }
	public function outputDocumentation() { echo "Excel file"; }
	
	public function getOutputFormat($input) {
		return "application/vnd.ms-excel";
	}
	
	public function execute(&$component, $input) {
		$progress_id = $input["input"]["progress_id"];
		$all = $input["input"]["all"];
		$session_id = isset($input["input"]["session_id"]) ? $input["input"]["session_id"] : null;
		
		$has_answers = PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer");
		
		$subjects = SQLQuery::create()->select("ExamSubject")->execute();
		$q = SQLQuery::create()->select("Applicant");
		if ($all) $q->whereNotValue("Applicant", "exam_attendance", "No");
		else $q->whereValue("Applicant", "exam_attendance", "Yes");
		if ($session_id <> null) $q->whereValue("Applicant", "exam_session", $session_id);
		$q->field("people")->field("applicant_id");
		$q->join("Applicant","People",array("people"=>"id"))->field("People","first_name")->field("People","last_name");
		$applicants = $q->execute();
		$applicants_ids = array();
		foreach ($applicants as $a) $applicants_ids[$a["people"]] = $a; 
		
		set_time_limit(300);
		
		error_reporting(E_ERROR | E_PARSE);
		require_once("component/lib_php_excel/PHPExcel.php");
		$excel = new PHPExcel();
		
		if (count($subjects) == 0) {
			$excel->getSheet(0)->setCellValueByColumnAndRow(0,1,"There is no exam subject, we cannot export any result");
			$style = $excel->getSheet(0)->getStyleByColumnAndRow(0, 1);
			$style->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKRED));
		} else if (count($applicants_ids) == 0) {
			$excel->getSheet(0)->setCellValueByColumnAndRow(0,1,"There is no applicant, we cannot export any result");
			$style = $excel->getSheet(0)->getStyleByColumnAndRow(0, 1);
			$style->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKRED));
		} else {
			PNApplication::$instance->application->updateTemporaryData($progress_id, "1");
			$nb = count($applicants_ids)*count($subjects)*($has_answers ? 2 : 1);
			$progress = 0;
			
			foreach ($subjects as $subject) {
				$versions = SQLQuery::create()->select("ExamSubjectVersion")->whereValue("ExamSubjectVersion","exam_subject",$subject["id"])->execute();
				$parts = SQLQuery::create()->select("ExamSubjectPart")->whereValue("ExamSubjectPart","exam_subject",$subject["id"])->orderBy("ExamSubjectPart","index")->field("id")->field("name")->execute();
				$parts_ids = array();
				foreach ($parts as $p) array_push($parts_ids, $p["id"]);
				$parts_questions = SQLQuery::create()->select("ExamSubjectQuestion")->whereIn("ExamSubjectQuestion","exam_subject_part",$parts_ids)->orderBy("ExamSubjectQuestion","index")->field("id")->field("exam_subject_part")->execute();
				for ($i = 0; $i < count($parts); $i++)
					$parts[$i]["questions"] = array();
				foreach ($parts_questions as $q) {
					for ($i = 0; $i < count($parts); $i++)
						if ($parts[$i]["id"] == $q["exam_subject_part"]) {
							array_push($parts[$i]["questions"], $q);
							break;
						}
				}
				$all_questions_ids = array();
				foreach ($parts as $p)
					foreach ($p["questions"] as $q)
						array_push($all_questions_ids, $q["id"]);
				
				for ($version = 0; $version < count($versions); $version++) {
					$version_name = count($versions) > 1 ? " Version ".chr(ord("A")+$version) : "";
					if ($has_answers) {
						set_time_limit(300);
						$sheet = new PHPExcel_Worksheet($excel, $subject["name"]."$version_name Answers");
						$excel->addSheet($sheet);
						// header
						$sheet->setCellValueByColumnAndRow(0, 1, "Applicant");
						$style = $sheet->getStyleByColumnAndRow(0, 1);
						$style->getFont()->setBold(true);
						$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$sheet->mergeCellsByColumnAndRow(0, 1, 2, 1);
						$sheet->setCellValueByColumnAndRow(0, 2, "ID");
						$sheet->setCellValueByColumnAndRow(1, 2, "First Name");
						$sheet->setCellValueByColumnAndRow(2, 2, "Last Name");
						$style = $sheet->getStyleByColumnAndRow(0, 2);
						$style->getFont()->setBold(true);
						$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$style = $sheet->getStyleByColumnAndRow(1, 2);
						$style->getFont()->setBold(true);
						$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$style = $sheet->getStyleByColumnAndRow(2, 2);
						$style->getFont()->setBold(true);
						$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$col = 3;
						for ($part_i = 0; $part_i < count($parts); $part_i++) {
							$sheet->setCellValueByColumnAndRow($col, 1, "Part ".($part_i+1)." - ".$parts[$part_i]["name"]);
							$style = $sheet->getStyleByColumnAndRow($col, 1);
							$style->getFont()->setBold(true);
							$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
							$sheet->mergeCellsByColumnAndRow($col, 1, $col+count($parts[$part_i]["questions"])-1, 1);
							for ($q_i = 0; $q_i < count($parts[$part_i]["questions"]); $q_i++) {
								$sheet->setCellValueByColumnAndRow($col, 2, "Q".($q_i+1));
								$style = $sheet->getStyleByColumnAndRow($col, 2);
								$style->getFont()->setBold(true);
								$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
								$col++;
							}
						}
						// answers
						$ids = SQLQuery::create()
							->select("ApplicantExamSubject")
							->whereValue("ApplicantExamSubject","exam_subject",$subject["id"])
							->whereValue("ApplicantExamSubject","exam_subject_version", $versions[$version]["id"])
							->whereIn("ApplicantExamSubject", "applicant", array_keys($applicants_ids))
							->field("ApplicantExamSubject","applicant")
							->executeSingleField();
						$answers = SQLQuery::create()->select("ApplicantExamAnswer")->whereIn("ApplicantExamAnswer","applicant",$ids)->whereIn("ApplicantExamAnswer","exam_subject_question",$all_questions_ids)->orderBy("ApplicantExamAnswer","applicant")->execute();
						$current_applicant = 0;
						$row = 2;
						foreach ($answers as $a) {
							if ($a["applicant"] <> $current_applicant) {
								$current_applicant = $a["applicant"];
								$row++;
								$sheet->setCellValueByColumnAndRow(0, $row, $applicants_ids[$current_applicant]["applicant_id"]);
								$sheet->setCellValueByColumnAndRow(1, $row, $applicants_ids[$current_applicant]["first_name"]);
								$sheet->setCellValueByColumnAndRow(2, $row, $applicants_ids[$current_applicant]["last_name"]);
								$progress++;
								if (($progress % 100) == 0) {
									$pc = 1+($progress*99/$nb);
									PNApplication::$instance->application->updateTemporaryData($progress_id, $pc);
								}
							}
							$index = array_search($a["exam_subject_question"], $all_questions_ids, true);
							if ($index === false) {
								$sheet->setCellValueByColumnAndRow(0, $row, "ERROR: There are answers to unknown questions");
								continue;
							}
							$sheet->setCellValueByColumnAndRow($index+3, $row, $a["answer"]);
						}
					}
					set_time_limit(300);
					$sheet = new PHPExcel_Worksheet($excel, $subject["name"].$version_name.($has_answers ? " Grades" : ""));
					$excel->addSheet($sheet);
					// header
					$sheet->setCellValueByColumnAndRow(0, 1, "Applicant");
					$style = $sheet->getStyleByColumnAndRow(0, 1);
					$style->getFont()->setBold(true);
					$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
					$sheet->mergeCellsByColumnAndRow(0, 1, 2, 1);
					$sheet->setCellValueByColumnAndRow(0, 2, "ID");
					$sheet->setCellValueByColumnAndRow(1, 2, "First Name");
					$sheet->setCellValueByColumnAndRow(2, 2, "Last Name");
					$style = $sheet->getStyleByColumnAndRow(0, 2);
					$style->getFont()->setBold(true);
					$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
					$style = $sheet->getStyleByColumnAndRow(1, 2);
					$style->getFont()->setBold(true);
					$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
					$style = $sheet->getStyleByColumnAndRow(2, 2);
					$style->getFont()->setBold(true);
					$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
					$sheet->setCellValueByColumnAndRow(3, 1, "Total Exam");
					$sheet->mergeCellsByColumnAndRow(3, 1, 3, 2);
					$style = $sheet->getStyleByColumnAndRow(3, 1);
					$style->getFont()->setBold(true);
					$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
					$col = 4;
					for ($part_i = 0; $part_i < count($parts); $part_i++) {
						$sheet->setCellValueByColumnAndRow($col, 1, "Total Part ".($part_i+1));
						$sheet->mergeCellsByColumnAndRow($col, 1, $col, 2);
						$style = $sheet->getStyleByColumnAndRow($col, 1);
						$style->getFont()->setBold(true);
						$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$col++;
					}
					for ($part_i = 0; $part_i < count($parts); $part_i++) {
						$sheet->setCellValueByColumnAndRow($col, 1, "Part ".($part_i+1)." - ".$parts[$part_i]["name"]);
						$style = $sheet->getStyleByColumnAndRow($col, 1);
						$style->getFont()->setBold(true);
						$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
						$sheet->mergeCellsByColumnAndRow($col, 1, $col+count($parts[$part_i]["questions"])-1, 1);
						for ($q_i = 0; $q_i < count($parts[$part_i]["questions"]); $q_i++) {
							$sheet->setCellValueByColumnAndRow($col, 2, "Q".($q_i+1));
							$style = $sheet->getStyleByColumnAndRow($col, 2);
							$style->getFont()->setBold(true);
							$style->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
							$col++;
						}
					}
					// grades
					$list = SQLQuery::create()
						->select("ApplicantExamSubject")
						->whereValue("ApplicantExamSubject","exam_subject",$subject["id"])
						->whereValue("ApplicantExamSubject","exam_subject_version", $versions[$version]["id"])
						->whereIn("ApplicantExamSubject", "applicant", array_keys($applicants_ids))
						->field("ApplicantExamSubject","applicant")
						->field("ApplicantExamSubject","score")
						->execute();
					$ids = array();
					foreach ($list as $a) {
						array_push($ids, $a["applicant"]);
						$applicants_ids[$current_applicant]["total_grade"] = $a["score"];
					}
					$grades = SQLQuery::create()->select("ApplicantExamAnswer")->whereIn("ApplicantExamAnswer","applicant",$ids)->whereIn("ApplicantExamAnswer","exam_subject_question",$all_questions_ids)->orderBy("ApplicantExamAnswer","applicant")->execute();
					$parts_grades = SQLQuery::create()->select("ApplicantExamSubjectPart")->whereIn("ApplicantExamSubjectPart","applicant",$ids)->whereIn("ApplicantExamSubjectPart","exam_subject_part",$parts_ids)->orderBy("ApplicantExamSubjectPart","applicant")->execute();
					$current_applicant = 0;
					$row = 2;
					foreach ($grades as $a) {
						if ($a["applicant"] <> $current_applicant) {
							$current_applicant = $a["applicant"];
							$row++;
							$sheet->setCellValueByColumnAndRow(0, $row, $applicants_ids[$current_applicant]["applicant_id"]);
							$sheet->setCellValueByColumnAndRow(1, $row, $applicants_ids[$current_applicant]["first_name"]);
							$sheet->setCellValueByColumnAndRow(2, $row, $applicants_ids[$current_applicant]["last_name"]);
							$sheet->setCellValueByColumnAndRow(3, $row, $applicants_ids[$current_applicant]["total_grade"]);
							while (count($parts_grades) > 0 && $parts_grades[0]["applicant"] == $current_applicant) {
								$part_id = $parts_grades[0]["exam_subject_part"];
								for ($i = 0; $i < count($parts_ids); ++$i) {
									if ($part_id == $parts_ids[$i]) {
										$sheet->setCellValueByColumnAndRow(4+$i, $row, $parts_grades[0]["score"]);
										break;
									}
								}
								array_shift($parts_grades);
							}
							$progress++;
							if (($progress % 100) == 0) {
								$pc = 1+($progress*99/$nb);
								PNApplication::$instance->application->updateTemporaryData($progress_id, $pc);
							}
						}
						$index = array_search($a["exam_subject_question"], $all_questions_ids, true);
						if ($index === false) {
							$sheet->setCellValueByColumnAndRow(0, $row, "ERROR: There are answers to unknown questions");
							continue;
						}
						$sheet->setCellValueByColumnAndRow($index+4+count($parts), $row, $a["score"]);
					}
				}
			}
			PNApplication::$instance->application->updateTemporaryData($progress_id, "100");
			$excel->removeSheetByIndex(0);
		}
		header("Content-Disposition: attachment; filename=\"ExamResults.xlsx\"");
		$writer = new PHPExcel_Writer_Excel2007($excel);
		$writer->save('php://output');
		
		PNApplication::$instance->application->updateTemporaryData($progress_id, "done");
	}
	
}
?>