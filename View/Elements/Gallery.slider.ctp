<div id="slides_viewport"><div id="slides"></div></div>
<script type="text/template" id="default_slide_template">
	<div class="feature-title hidden" style="margin-left:-15px; margin-right:-15px;">
		<h3><%= Slide.title %></h3>
		<div class="feature-cta btn btn-primary"><a href="<%= Slide.cta_link %>"><%= Slide.cta_text %></a></div>
	</div>
	<img src="<%= Image.resized %>" alt="<%= Slide.title %>" />
</script>
<script type="text/javascript">
	// Not needed for Anmed but I am leaving it here in case
	var node_id = <?php echo (!empty($node))? $node['Node']['id'] : 0; ?>;
	var slide_template =  "<?php echo (!empty($template_id)) ? $template_id : '#default_slide_template'; ?>";

	SlideModel = Backbone.Model.extend({

	})
	slide = new SlideModel({
		url: '/slides'
	})
	SlideView = Backbone.View.extend({
		className: 'slide',
		initialize: function(){
			this.model.on('remove', this.remove, this);
		},
		template: _.template($(slide_template).html()),
		render: function () {
			var attributes = this.model.toJSON();
			this.$el.html(this.template(attributes));
			this.$el.hammer();
			return this;
		}

	});
	SlideListView = Backbone.View.extend({
		slide_width: <?php echo (!empty($slide_width))? $slide_width : 1170; ?>,
		slide_height: <?php echo (!empty($slide_height))? $slide_height : 510; ?>,
		max_height: <?php echo (!empty($max_height))? $max_height : 0; ?>,
		looping :  '<?php echo (isset($looping) && !$looping) ? false : true; ?>',
		slide_number : 1,

		className:'slide_collection',
		initialize: function (){
			this.collection.on('add', this.addOne, this);
			this.collection.on('reset', this.auto_slide, this);
			this.collection.on('reset', this.addAll, this);
			this.collection.on('move_start', this.hide_text);
			this.collection.on('move_end', this.show_text);
			this.collection.on('move_end', this.set_arrow_buttons);
			this.slide_width = this.set_slide_width();
			$(window).bind('resize', _.bind(this.reset_margin, this));

		},
		events:{
			"click .nav_button#move_right":"move_right",
			"click .nav_button#move_left":"move_left",
			"click .nav_button":"disable_auto_slide",
			'swipeleft':'move_right',
			'swiperight':'move_left'
		},
		addOne: function(slide){
			var slideView = new SlideView({model:slide});
			var index = this.collection.indexOf(slide);
			if (index != 0) {
				this.$el.append(slideView.render().el);
			}else if (index == 0) {
				this.$el.prepend(slideView.render().el);
			}else{
				this.$el.append(slideView.render().el);
			}
		},
		addAll: function (){
			$('#slides').show();
			this.collection.forEach(this.addOne, this);
			if (this.collection.length >= 2) {
				this.single_image = false;
				this.$el.append('<div class="nav_button" id="move_left"><span class="glyphicon glyphicon-chevron-left"></span></div><div class="nav_button" id="move_right"><span class="glyphicon glyphicon-chevron-right"></span></div>');
			}else{
				this.single_image = true;
				this.disable_auto_slide();
			}
			this.show_text();
			this.reset_margin();
			this.set_arrow_buttons();
		},
		move_right : function (e) {
			if (e) {
				e.preventDefault();
			}
			first_slide = this.collection.shift();
			// Take the first slide and put it at the end;
			this.collection.add(first_slide)
			this.move_slide(this.slide_width);
		},

		move_left : function (e) {
			if (e) {
				e.preventDefault();
			}
			last_slide = this.collection.pop();
			// Take the last slide and move it to the beginning
			this.collection.add(last_slide, {at:0})
			this.move_slide(this.slide_width, 'left');
		},

		// move the slide a direction and load slides
		move_slide: function (distance, direction) {
			this.reset_margin();
			this.collection.trigger('move_start');
			this.undelegateEvents();
			distance = parseInt(distance);
			current_margin = parseInt($('.slide_collection').css('margin-left').replace('px',''));
			if (direction && direction == 'left') {
				this.slide_number = this.slide_number - 1;

				// This accounts for the newly added element to the beginning of the floated group
				positioner =  current_margin - distance;
				new_margin = positioner + distance;
				$('.slide_collection').css({'margin-left':positioner+'px'});
				$('.slide_collection').transition({'margin-left':new_margin+'px',complete:$.proxy(function (){this.delegateEvents();this.collection.trigger('move_end');}, this)});
			}else{
				this.slide_number = this.slide_number + 1;
				// This accounts for the newly removed element at the beginning of the floated group
				positioner =  current_margin + distance;
				new_margin = positioner - distance;
				$('.slide_collection').css({'margin-left':positioner+'px'});
				$('.slide_collection').transition({'margin-left':new_margin+'px',complete:$.proxy(function (){this.delegateEvents();this.collection.trigger('move_end');}, this)});
			}
		},
		auto_slide : function () {
			this.auto_slider = setInterval(this.move_right.bind(this), 8000);
		},
		// Disable auto rotation once a button is clicked
		disable_auto_slide : function () {
			clearInterval(this.auto_slider);
		},
		// Hide cta and description text
		hide_text: function (){
			$('.slide .feature-title').addClass('hidden');
		},
		show_text:function () {
			type = 'even';
			if (this.single_image) {
				type = 'odd';
			}
			$('.slide:nth-child('+type+') .feature-title').removeClass('hidden');
		},
		// Set the slider width based on the screen size
		set_slide_width: function () {
			return parseInt($('#slides_viewport').css('width').replace('px', ''));
		},
		// adjust the margin when the window gets resized
		reset_margin:function () {
			default_slide_width = this.slide_width;
			default_slide_height = this.slide_height;
			slide_width = this.set_slide_width();
			slide_height = slide_width*default_slide_height/default_slide_width
			if (this.max_height > 0 && slide_height > this.max_height) {
				slide_height = this.max_height;
			}
			reset_margin = slide_width * -1;
			if (this.single_image) {
				reset_margin = 0;
			}

			$('.slide_collection').css('margin-left', reset_margin);
			$('.slide_collection .slide').css({'width':slide_width, 'height':slide_height});
			$('#slides').css('height', slide_height);
			$('#move_right').css('left', slide_width - 110);
		},
		set_arrow_buttons : function () {

			if (!slideListView.looping) {
				if (slideListView.slide_number == this.length) {
					$('.nav_button#move_right').hide();
					$('.nav_button#move_left').show();
				}else if (slideListView.slide_number == 1){
					$('.nav_button#move_right').show();
					$('.nav_button#move_left').hide();
				}
			}
		}
	})

	SlideList = Backbone.Collection.extend({
		url: '/slides/all/'+node_id,
		model:SlideModel
	})

	slideList = new SlideList();



	$(document).ready(function () {
		slideListView = new SlideListView({collection:slideList});
		// This needs to be removed. Fetch is for lazy loading after page load.
		slideList.fetch({reset:true});
		$('#slides').html(slideListView.$el);
	})
</script>
