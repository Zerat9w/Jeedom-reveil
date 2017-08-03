<?php
if (!isConnect('admin')) {
throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'reveil');
$eqLogics = eqLogic::byType('reveil');
?>
<div class="row row-overflow">
	<div class="col-lg-2">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-default eqLogicAction" style="width : 50%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
				<?php
					foreach ($eqLogics as $eqLogic) 
						echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
				?>
			</ul>
		</div>
	</div>
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend>{{Mes reveils}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
				<center>
					<i class="fa fa-plus-circle" style="font-size : 7em;color:#94ca02;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;;color:#94ca02"><center>Ajouter</center></span>
			</div>
			<?php
				foreach ($eqLogics as $eqLogic) {
					$opacity = '';
					if ($eqLogic->getIsEnable() != 1) {
						$opacity = '
						-webkit-filter: grayscale(100%);
						-moz-filter: grayscale(100);
						-o-filter: grayscale(100%);
						-ms-filter: grayscale(100%);
						filter: grayscale(100%); opacity: 0.35;';
					}
					echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
					echo "<center>";
					echo '<img src="plugins/reveil/doc/images/reveil_icon.png" height="105" width="95" />';
					echo "</center>";
					echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
					echo '</div>';
				}
			?>
		</div>
	</div>  
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
		<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> Sauvegarder</a>
		<a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> Supprimer</a>
		<a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> Configuration avancée</a>
		<a class="btn btn-default eqLogicAction pull-right expertModeVisible " data-action="copy"><i class="fa fa-copy"></i>{{Dupliquer}}</a>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation">
				<a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay">
					<i class="fa fa-arrow-circle-left"></i>
				</a>
			</li>
			<li role="presentation" class="active">
				<a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true">
					<i class="fa fa-tachometer"></i> Equipement</a>
			</li>
			<li role="presentation" class="">
				<a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false">
					<i class="fa fa-list-alt"></i> Commandes</a>
			</li>
			<li role="presentation" class="">
				<a href="#conditiontab" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false">
					<i class="fa fa-list-alt"></i> {{Conditions d'éxécution}}</a>
			</li>
			<li role="presentation" class="">
				<a href="#actiontab" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false">
					<i class="fa fa-list-alt"></i> {{Actions}}</a>
			</li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group ">
							<label class="col-sm-2 control-label">{{Nom du réveil}}
								<sup>
									<i class="fa fa-question-circle tooltips" title="Indiquer le nom de votre réveil" style="font-size : 1em;color:grey;"></i>
								</sup>
							</label>
							<div class="col-sm-5">
								<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom du groupe de zones}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" >{{Objet parent}}
								<sup>
									<i class="fa fa-question-circle tooltips" title="Indiquer l'objet dans lequel le widget de ce réveil apparaîtra sur le dashboard" style="font-size : 1em;color:grey;"></i>
								</sup>
							</label>
							<div class="col-sm-5">
								<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
									<option value="">{{Aucun}}</option>
									<?php
										foreach (object::all() as $object) 
											echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-2 control-label">
								{{Catégorie}}
								<sup>
									<i class="fa fa-question-circle tooltips" title="Choisissez une catégorie
								Cette information n'est pas obligatoire mais peut être utile pour filtrer les widgets" style="font-size : 1em;color:grey;"></i>
								</sup>
							</label>
							<div class="col-md-8">
								<?php
								foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
									echo '<label class="checkbox-inline">';
									echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
									echo '</label>';
								}
								?>

							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" >
								{{Etat du widget}}
								<sup>
									<i class="fa fa-question-circle tooltips" title="Choisissez les options de visibilité et d'activation
								Si l'équipement n'est pas activé il ne sera pas utilisable dans Jeedom, mais visible sur le dashboard
								Si l'équipement n'est pas visible il sera caché sur le dashboard, mais utilisable dans Jeedom" style="font-size : 1em;color:grey;"></i>
								</sup>
							</label>
							<div class="col-sm-5">
								<label>{{Activer}}</label>
								<input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="isEnable"/>
								<label>{{Visible}}</label>
								<input type="checkbox" class="eqLogicAttr" data-label-text="{{Visible}}" data-l1key="isVisible"/>
							</div>
						</div><div class="form-group">
							<label class="col-sm-2 control-label" >
								{{Jours fériés}}
								<sup>
									<i class="fa fa-question-circle tooltips" title="Si actif le réveil sera ignoré les jours fériés" style="font-size : 1em;color:grey;"></i>
								</sup>
							</label>
							<div class="col-sm-5">
								<label>{{Ignorer}}</label>
								<input type="checkbox" class="eqLogicAttr" data-label-text="{{Ignorer}}" data-l1key="configuration" data-l2key="isHolidays"/>
								</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Configurer l'heure du réveil}}
								<sup>
									<i class="fa fa-question-circle tooltips" title="Configurer l'heure du réveil"></i>
								</sup>
							</label>
							<div class="col-md-8 input-group">
								<label class="checkbox-inline">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Schedule" data-l3key="1" />
									{{Lundi}}
								</label>
								<label class="checkbox-inline">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Schedule" data-l3key="2" />
									{{Mardi}}
								</label>
								<label class="checkbox-inline">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Schedule" data-l3key="3" />
									{{Mercredi}}
								</label>
								<label class="checkbox-inline">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Schedule" data-l3key="4" />
									{{Jeudi}}
								</label>
								<label class="checkbox-inline">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Schedule" data-l3key="5" />
									{{Vendredi}}
								</label>
								<label class="checkbox-inline">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Schedule" data-l3key="6" />
									{{Samedi}}
								</label>
								<label class="checkbox-inline">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="Schedule" data-l3key="0" />
									{{Dimanche}}
								</label>
							</div>
							<div class="input-group">
								<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Schedule" data-l3key="Heure" >
									<?php
										for($loop=0; $loop<24; $loop++)
											echo '<option value="'.$loop.'">'.$loop.'</option>';
									?>
								</select>
								<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Schedule" data-l3key="Minute" >
									<?php
										for($loop=0; $loop<60; $loop++)
											echo '<option value="'.$loop.'">'.$loop.'</option>';
									?>
								</select>
							</div>
						</div>
					</fieldset>
				</form>
			</div>		
			<div role="tabpanel" class="tab-pane" id="conditiontab">
				<form class="form-horizontal">
					<fieldset>
						<legend>{{Les conditions d'exécution :}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="Saisir toutes les conditions d'exécution de la gestion"></i>
							</sup>
							<a class="btn btn-success btn-xs conditionAttr" data-action="add" style="margin-left: 5px;">
								<i class="fa fa-plus-circle"></i>
								{{Ajouter une Condition}}
							</a>
						</legend>
					</fieldset>
				</form>			
				<table id="table_condition" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th></th>
							<th>Condition</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>				
			<div role="tabpanel" class="tab-pane" id="actiontab">
				<form class="form-horizontal">
					<fieldset>
						<legend>{{Les actions:}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="Saisir toutes les actions à mener à l'ouverture"></i>
							</sup>
							<a class="btn btn-success btn-xs ActionAttr" data-action="add" style="margin-left: 5px;">
								<i class="fa fa-plus-circle"></i>
								{{Ajouter une Action}}
							</a>
						</legend>
					</fieldset>
				</form>					
				<table id="table_action" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th></th>
							<th>Action</th>
							<th>
								Type de reveil.append
								<sup>
									<i class="fa fa-question-circle tooltips" title="Séléctioner le type de réveil"></i>
								</sup
							</th>
							<th>Parametre</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>		
			<div role="tabpanel" class="tab-pane" id="commandtab">	
				<table id="table_cmd" class="table table-bordered table-condensed">
				    <thead>
					<tr>
					    <th>Nom</th>
					    <th>Paramètre</th>
					</tr>
				    </thead>
				    <tbody></tbody>
				</table>
			</div>	
		</div>
	</div>
</div>

<?php include_file('desktop', 'reveil', 'js', 'reveil'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
