<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
	<html>
		<head>
			<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
				<title>Dynatree - Example</title>

				<script src="../jquery/jquery.js" type="text/javascript"></script>
				<script src="../jquery/jquery-ui.custom.js" type="text/javascript"></script>
				<script src="../jquery/jquery.cookie.js" type="text/javascript"></script>

				<link href="../src/skin/ui.dynatree.css" rel="stylesheet" type="text/css" id="skinSheet">
					<script src="../src/jquery.dynatree.js" type="text/javascript"></script>

					<!-- (Irrelevant source removed.) -->

					<script type="text/javascript">
					$(function(){
						$("#tree").dynatree({
							// using default options
						});
						<!-- (Irrelevant source removed.) -->
					});
					</script>
				</head>

				<body class="example">
					<h1>Example: Default</h1>
					<p class="description">
					This tree uses default options.<br>
					It is initalized from a hidden &lt;ul> element on this page.
					</p>
						<div>
						Skin:
							<select id="skinCombo" size="1">
								<option value="skin">Standard ('/skin/')</option>
								<option value="skin-vista">Vista ('/skin-vista/')</option>
							</select>
						</div>

						<div id="tree">
							<ul id="treeData" style="display: none;">
								<li id="id1" title="Look, a tool tip!">item1 with key and tooltip
									<li id="id2">item2
										<li id="id3" class="folder">Folder with some children
											<ul>
												<li id="id3.1">Sub-item 3.1
													<ul>
														<li id="id3.1.1">Sub-item 3.1.1
															<li id="id3.1.2">Sub-item 3.1.2
															</ul>
															<li id="id3.2">Sub-item 3.2
																<ul>
																	<li id="id3.2.1">Sub-item 3.2.1
																		<li id="id3.2.2">Sub-item 3.2.2
																		</ul>
																	</ul>
																	<li id="id4" class="expanded">Document with some children (expanded on init)
																		<ul>
																			<li id="id4.1"  class="active focused">Sub-item 4.1 (active and focus on init)
																				<ul>
																					<li id="id4.1.1">Sub-item 4.1.1
																						<li id="id4.1.2">Sub-item 4.1.2
																						</ul>
																						<li id="id4.2">Sub-item 4.2
																							<ul>
																								<li id="id4.2.1">Sub-item 4.2.1
																									<li id="id4.2.2">Sub-item 4.2.2
																									</ul>
																								</ul>
																							</ul>
																						</div>

																						<!-- (Irrelevant source removed.) -->
																					</body>
																				</html>
