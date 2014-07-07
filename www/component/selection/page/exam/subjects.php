<?php
require_once("/../SelectionPage.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_subjects extends SelectionPage {
	
	public function getRequiredRights() {return array("see_exam_subject");}
	
	public function executeSelectionPage() {
		$this->requireJavascript("vertical_layout.js");
		$this->onload("new vertical_layout('subjects_page_container');");
		$this->onload("new vertical_layout('subject_container');");
		$this->requireJavascript("horizontal_layout.js");
		$this->onload("new horizontal_layout('subjects_page_content');");
		theme::css($this, "section.css");
		require_once("component/selection/SelectionExamJSON.inc");
		$q = SQLQuery::create()->select("ExamSubject");
		SelectionExamJSON::ExamSubjectSQL($q);
		$subjects = $q->execute();
		?>
		<div id='subjects_page_container' style='width:100%;height:100%;overflow:hidden'>
			<div class='page_title'>
				Written Exam Subjects
			</div>
			<div layout="fill" id='subjects_page_content' style="padding:10px;overflow:hidden">
				<div id='subjects' class='section_block' style="display:inline-block;height:100%;background-color:white;overflow-y:auto;margin-right:10px;" layout="350">
					<span id='no_subject' style='display:none;position:absolute;'>
						<i>No subject defined yet</i>
					</span>
				</div>
				<div id='subject_container' class='section_block' style="display:inline-block;height:100%;background-color:white;overflow:hidden" layout="fill">
					<div style='height:100%;padding:3px;overflow:hidden' layout="fill">
						<iframe id='subject_frame' style='height:100%;width:100%;border:none' src='/static/application/message.html?text=<?php echo urlencode("Select a subject to display it here");?>'>
						</iframe>
					</div>
					<div class='page_footer' id='subject_footer' style='visibility:hidden'>
						<button class="action" id='save_button'><img src='<?php echo theme::$icons_16['save'];?>'/> Save</button>
						<button class="action" id='cancel_button'><img src='<?php echo theme::$icons_16['cancel'];?>'/> Cancel modifications</button>
					</div>
				</div>
			</div>
			<div class="page_footer">
				<button class='action' onclick="newSubject();"><img src='<?php echo theme::make_icon("/static/selection/exam/subject_white.png",theme::$icons_10['add'],"right_bottom");?>'/> New Subject</button>
				<button class='action' onclick="copySubject();"><img src='<?php echo theme::$icons_16['copy'];?>'/> Copy a subject from previous campaign</button>
			</div>
		</div>
		<style type='text/css'>
		.subject_div {
		}
		.subject_box {
			margin: 5px;
			padding: 5px;
			text-align: center;
			border: 1px solid rgba(0,0,0,0);
			border-radius: 5px;
			display: inline-block;
			cursor: pointer;
			width: 150px;
		}
		.subject_box.selected {
			border: 1px solid #F0D080;
			background-color: #FFF0D0;
			background: linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%);
		}
		.subject_box:hover {
			border: 1px solid #F0D080;
		}
		.subject_box .subject_name {
			font-size: 12pt;
			font-weight: bold;
			color: black;
		}
		.subject_box .subject_points {
			font-size: 10pt;
			color: #606060;
		}
		.subject_actions_container {
			display: inline-block;
		}
		.subject_actions_container>button {
			display: block;
			margin: 5px 0px;
		}
		</style>
		<script type='text/javascript'>
		var subjects = <?php echo SelectionExamJSON::ExamSubjectsJSON($subjects); ?>;
		var subjects_controls = [];

		function SubjectControl(subject) {
			var t=this;
			this.subject = subject;
			this._init = function() {
				var container = document.getElementById('subjects');
				this.div = document.createElement("DIV"); container.appendChild(this.div);
				this.div.className = "subject_div";
				this.box = document.createElement("DIV"); this.div.appendChild(this.box);
				this.box.className = "subject_box";
				this.box.innerHTML = "<img src='/static/selection/exam/exam_subject_48.png'/><br/>";
				var name = document.createElement("DIV"); this.box.appendChild(name);
				name.className = "subject_name";
				name.appendChild(document.createTextNode(subject.name));
				window.top.datamodel.registerCellSpan(window, "ExamSubject", "name", subject.id, name);
				var points = document.createElement("DIV"); this.box.appendChild(points);
				points.className = "subject_score";
				var nb_points = document.createElement("SPAN");
				nb_points.innerHTML = subject.max_score;
				points.appendChild(nb_points);
				window.top.datamodel.registerCellSpan(window, "ExamSubject", "max_score", subject.id, nb_points);
				points.appendChild(document.createTextNode(" point(s)"));
				this.box.onclick = function() {
					var frame = document.getElementById('subject_frame');
					new LoadingFrame(frame);
					frame.src = "/dynamic/selection/page/exam/subject?id="+subject.id;
					showSubjectActions(subject);
					this.className = "subject_box selected";
					for (var i = 0; i < subjects_controls.length; ++i)
						if (subjects_controls[i] != t) subjects_controls[i].box.className = "subject_box";
				};
				this.actions_container = document.createElement("DIV");
				this.actions_container.className = "subject_actions_container";
				this.div.appendChild(this.actions_container);

				var export_button = document.createElement("BUTTON");
				this.actions_container.appendChild(export_button);
				export_button.className = "action";
				export_button.innerHTML = "<img src='"+theme.icons_16._export+"'/> Export to...";
				export_button.onclick = function() {
					alert("Not yet implemented."); // TODO
				};

				var remove_button = document.createElement("BUTTON");
				this.actions_container.appendChild(remove_button);
				remove_button.className = "action important";
				remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/> Remove this subject";
				// TODO disable remove button if already some grades, or eligibility rules associated to it
				remove_button.onclick = function() {
					confirm_dialog("Are you sure you want to remove this exam ?",
						function(answer){
							if(!answer) return;
							var locker = lock_screen(null,"Removing subject...");
							service.json("selection","exam/remove_subject",{id:subject.id},function(res){
								unlock_screen(locker);
								if(!res)
									error_dialog("An error occured");
								else {
									t.div.parentNode.removeChild(t);
									subjects.remove(subject);
									subjects_controls.remove(t);
								}
							});
						}
					);
				};
				require("animation.js",function() {
					animation.appearsOnOver(t.div, [t.actions_container]);
				});
			};
			this._init();
		}

		function newSubject() {
			var frame = document.getElementById('subject_frame');
			new LoadingFrame(frame);
			frame.src = "/dynamic/selection/page/exam/subject?id=-1";
			showSubjectActions(null);
		}

		function copySubject() {
			require("popup_window.js",function() {
				var pop = new popup_window(
					"Create Exam Subject",
					theme.build_icon("/static/selection/exam/exam_16.png",theme.icons_10.add,"right_bottom"),
					""
				);
				pop.setContentFrame("/dynamic/selection/page/exam/copy_subject");
				pop.onclose = function() {
					location.reload();
				};
				pop.show();
			});
		}

		function showSubjectActions(subject) {
			document.getElementById('subject_footer').style.visibility = "visible";
			var save_button = document.getElementById('save_button');
			save_button.disabled = "disabled";
			var cancel_button = document.getElementById('cancel_button');
			cancel_button.disabled = "disabled";
			waitFrameContentReady(
				document.getElementById('subject_frame'),
				function(win) {
					if (typeof win.exam_subject_manager == 'undefined') return false;
					return true;
				},
				function(win) {
					win.pnapplication.autoDisableSaveButton(save_button);
					win.pnapplication.autoDisableSaveButton(cancel_button);
					save_button.onclick = function() {
						var locker = lock_screen(null, "Saving subject...");
						win.exam_subject_manager.save(function(subj) {
							unlock_screen(locker);
							if (subj == null) return; // error case
							if (subject == null) {
								// new subject
								subjects.push(subj);
								subjects_controls.push(new SubjectControl(subj));
							} else {
								// updated subject
								for (var i = 0; i < subjects.length; ++i) {
									if (subjects[i].id == subj.id) {
										subjects[i].name = subj.name;
										window.top.datamodel.cellChanged("ExamSubject", "name", subj.id, subj.name);
										subjects[i].max_score = subj.max_score;
										window.top.datamodel.cellChanged("ExamSubject", "max_score", subj.id, subj.max_score);
									}
								}
							}
						});
					};
					cancel_button.onclick = function() {
						if (subject == null) {
							// new subject, let's restart
							win.pnapplication.cancelDataUnsaved();
							document.getElementById('subject_frame').src = "about:blank";
							newSubject();
						} else {
							win.pnapplication.cancelDataUnsaved();
							win.location.reload();
						}
					};
				}
			);
		}

		if (subjects.length == 0) {
			var no_subject = document.getElementById('no_subject');
			no_subject.style.position = "static";
			no_subject.style.display = "";
		} else {
			for (var i = 0; i < subjects.length; ++i)
				subjects_controls.push(new SubjectControl(subjects[i]));
		}
		</script>
		<?php 
	}
}
?>