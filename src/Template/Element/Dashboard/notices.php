<!-- AngularJS Core (v1.5.8) -->
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.8/angular.min.js"></script>
 
<!-- Angular Sanitize (must match version 1.5.8) -->
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.8/angular-sanitize.js"></script>
<?php //POCOR-9863: restore bullets/numbering in rich-text notice messages (reset.css removes them) ?>
<style>
	#news .notice-message ul,
	#news .notice-message ol { padding-inline-start: 20px; margin: 0 0 10px 0; }
	#news .notice-message ul { list-style: disc outside; }
	#news .notice-message ol { list-style: decimal outside; }
	#news .notice-message ul ul { list-style-type: circle; }
	#news .notice-message ul ul ul { list-style-type: square; }
	#news .notice-message li { display: list-item; }
</style>
<h3><?= __('Notices'); ?></h3>
<div class="row dashboard-container">
	<div id="news">
		<div class="dashboard-content margin-top-10">
			<div>
				<ul class="list-group">
					<li class="list-group-item" ng-if="!DashboardController.notices" ng-cloak>
						<div class="notice-message">
							<p><?= __('No Notices'); ?></p>
						</div>
					</li>
					<li class="list-group-item" ng-repeat="notice in DashboardController.notices | orderBy:'order'" ng-cloak>
						<div class="notice-message">
						<p ng-bind-html="notice.message"></p>

                                                        <!-- <p ng-if="notice.message|getUrl">
                                                           <iframe width="200" height="200" src="{{trustedUrl(notice.message|getUrl)}}" frameborder="0" allowfullscreen ></iframe> 
                                                        </p> -->
						</div>
						<!-- To add the following mapping when notice attachment is added -->
						<div class="notice-attachments" ng-show="notice.attachment">
							<p><?= __('Attachments') ?>:</p>
							<ul>
								<li> <!-- Link for Attachments Here --> </li>
							</ul>
						</div>
					</li>
					<li class="list-group-item" ng-if="DashboardController.notices && DashboardController.notices.length == 0">
						<div class="notice-message">
							<p><?= __('Loading'); ?> ...</p>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
