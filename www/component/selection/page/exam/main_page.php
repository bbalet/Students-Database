<?php

require_once("/../selection_page.inc");
class page_exam_main_page extends selection_page {
	
	public function get_required_rights() {}
	
	public function execute_selection_page(&$page) {
		$page->add_javascript("/static/widgets/page_header.js");
		$page->add_javascript("/static/widgets/vertical_layout.js");
		$page->onload("new vertical_layout('container');");
		$page->onload("new page_header('page_header',true);");
		$page->onload("new exam_subject_main_page('exam_content');");
		?>
		<div id = "container" style = "width:100%; height:100%">
			<div id = "page_header" icon = "/static/selection/exam/exam_16.png" title = "Entrance Examinations">
				<div class = "button" onclick = "location.assign('/dynamic/selection/page/selection_main_page');"><img src = "<?php echo theme::$icons_16['back'];?>"/> Back to selection</div>
				<div class = "button" onclick = "location.assign('/dynamic/selection/page/exam/sessions');">Exam Sessions</div>
				<div class = "button" onclick = "location.assign('/dynamic/selection/page/exam/results');">Exam Results</div>
			</div>
			<div id = "page_content" style = "overflow:auto" layout = "fill">
				<div id = "exam_content"></div>
				<div id = "eligibility_rules_content">TODO eligibility rules main screen</div>
			</div>
		</div>
		<script type = "text/javascript">
			function exam_subject_main_page(container){
				var t = this;
				if(typeof container == "string")
					container = document.getElementById(container);
				t.table = document.createElement('table');
				
				t.can_manage = <?php
						$can_manage = PNApplication::$instance->selection->canManageExamSubjectQuestions();
						echo json_encode($can_manage[0]).";";?>
				t.can_see = <?php echo json_encode(PNApplication::$instance->user_management->has_right("see_exam_subject"));?>;
						
				t.all_exams = <?php $exams = PNApplication::$instance->selection->getAllExamSubjects();
						echo "[";
						$first = true;
						foreach($exams as $e){
							if(!$first)
								echo ", ";
							$first = false;
							echo "{name:".json_encode($e["name"]).", id:".json_encode($e["id"])."}";
						}
						echo "];";
					?>
				
				t._init = function(){
					// Check the readable right
					if(!t.can_see)
						return;
					t.section = new section("","Exams Subjects",t.table, false);
					t._setTableContent();
					container.appendChild(t.section.element);
					t._setStyle();
				}
				
				t._setStyle = function(){
					container.style.paddingTop = "20px";
					container.style.paddingLeft = "20px";
					container.style.paddingRight = "20px";
				}
				
				t._setTableHeaderAndStyle = function(){
					var th = document.createElement("th");
					th.innerHTML = "Exams Subjects";
					t.table.appendChild((document.createElement("tr")).appendChild(th));
					//Set the style
					setCommonStyleTable(t.table, th, "#DADADA");
					t.table.marginLeft = "10px";
					t.table.marginRight = "10px";
					t.table.width = "98%";
				}
				
				t._setTableContent = function(){
					//set the body
					if(t.all_exams.length > 0)
						var ul = document.createElement("ul");
					for(var i = 0; i < t.all_exams.length; i++){
						var tr = document.createElement("tr");
						t._addExamRow(tr,i);
						ul.appendChild(tr);
					}
					if(t.all_exams.length > 0)
						t.table.appendChild(ul);
					
					//set the footer
					var tr_foot = document.createElement("tr");
					var td_foot = document.createElement("td");
					var create_button = document.createElement("div");
					create_button.className = "button";
					create_button.innerHTML = "<img src = '"+theme.icons_16.add+"'/> Create a subject";
					create_button.onclick = function(){
						location.assign("/dynamic/selection/page/exam/create_subject");
					};
					td_foot.appendChild(create_button);
					tr_foot.appendChild(td_foot);
					t.table.appendChild(tr_foot);
				}
				
				t._addExamRow = function(tr,i){
					var td_name = document.createElement("td");
					var li = document.createElement("li");
					li.innerHTML = t.all_exams[i].name;
					td_name.appendChild(li);
					tr.appendChild(td_name);
					
					see_button = t._createButton("<img src = '"+theme.icons_16.search+"'/> See",t.all_exams[i].id);
					see_button.onclick = function(){
						location.assign("/dynamic/selection/page/exam/subject?id="+this.id+"&readonly=true");
					};
					td_see = document.createElement("td")
					td_see.appendChild(see_button);
					tr.appendChild(td_see);
					
					export_button = t._createButton("<img src = '"+theme.icons_16.export+"'/> Export",t.all_exams[i].id);
					export_button.onclick = function(){
						var t2 = this;
						var menu = new context_menu();
						menu.addTitleItem("", "Export format");
						menu.addIconItem('/static/data_model/excel_16.png', 'Excel 2007 (.xlsx)', function() { t._export_subject('excel2007',false,t2.id); });
						menu.addIconItem('/static/data_model/excel_16.png', 'Excel 5 (.xls)', function() { t._export_subject('excel5',false,t2.id); });
						menu.addIconItem('/static/selection/exam/sunvote_16.png', 'SunVote ETS compatible format', function() { t._export_subject('excel2007',true,t2.id); });
						menu.showBelowElement(this);
					};
					td_export = document.createElement("td")
					td_export.appendChild(export_button);
					tr.appendChild(td_export);
					
					if(t.can_manage){
						edit_button = t._createButton("<img src = '"+theme.icons_16.edit+"'/> Edit",t.all_exams[i].id);
						edit_button.onclick = function(){
							location.assign("/dynamic/selection/page/exam/subject?id="+this.id);
						};
						td_edit = document.createElement("td")
						td_edit.appendChild(edit_button);
						tr.appendChild(td_edit);
					}
				}
				
				t._createButton = function(content, id){
					var div = document.createElement("div");
					div.innerHTML = content;
					div.className = "button";
					div.id = id;
					return div;
				}				
				
				t._export_subject = function(format,compatible_clickers,exam_id){
					var form = document.createElement('form');
					form.action = "/dynamic/selection/service/exam/export_subject";
					form.method = "POST";
					var input = document.createElement("input");
					input.type = "hidden";
					input.name = "format";
					input.value = format;
					form.appendChild(input);
					var input2 = document.createElement("input");
					input2.type = "hidden";
					input2.value = exam_id;
					input2.name = "id";
					form.appendChild(input2);
					if(compatible_clickers){
						var input3 = document.createElement("input");
						input3.type = "hidden";
						input3.value = "true";
						input3.name = "clickers";
						form.appendChild(input3);
					}
					document.body.appendChild(form);
					form.submit();
				}
				
				require(["section.js","context_menu.js"],function(){
					t._init();
				});
				
			}
		</script>
		<?php
	}
}
?>