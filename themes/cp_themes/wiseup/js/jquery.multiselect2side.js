/*
 * multiselect2side jQuery plugin
 *
 * Copyright (c) 2010 Giovanni Casassa (senamion.com - senamion.it)
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://www.senamion.com
 *
 */

(function($)
{
	jQuery.fn.multiselect2side = function (o) {

		o = $.extend({
			selectedPosition: 'right'
		}, o);


		return this.each(function () {
			var	el = $(this);

			var	originalName = $(this).attr("name");
			if (originalName.indexOf('[') != -1)
				originalName = originalName.substring(0, originalName.indexOf('['));
				originalName= originalName.replace("field_id_", "fl_");

			var	nameDx = originalName + "ms2side__dx";
			var	nameSx = originalName + "ms2side__sx";
			var size = $(this).attr("size");
			// SIZE MIN
			if (size < 6) {
				$(this).attr("size", "6");
				size = 6;
			}

			// CREATE NEW ELEMENT (AND HIDE IT) AFTER THE HIDDED ORGINAL SELECT <div class='clear'></div>
			var htmlToAdd = 
				"<div class='ms2side__div'>" +
					"<div class='ms2side__select'>" +
						"<select name='" + nameSx + "' id='" + nameSx + "' size='" + size + "' multiple='multiple' ></select>" +
					"</div>" +
					"<div class='ms2side__options'>" +
						((o.selectedPosition == 'right')
						?
						("<p class='AddOne' title='Add Selected'>&rsaquo;</p>" +
						"<p class='AddAll' title='Add All'>&raquo;</p>" +
						"<p class='RemoveOne' title='Remove Selected'>&lsaquo;</p>" +
						"<p class='RemoveAll' title='Remove All'>&laquo;</p>")
						:
						("<p class='AddOne' title='Add Selected'>&lsaquo;</p>" +
						"<p class='AddAll' title='Add All'>&laquo;</p>" +
						"<p class='RemoveOne' title='Remove Selected'>&rsaquo;</p>" +
						"<p class='RemoveAll' title='Remove All'>&raquo;</p>")
						) +
					"</div>" +
					"<div class='ms2side__select'>" +
						"<select name='" + nameDx + "' id='" + nameDx + "' size='" + size + "' multiple='multiple' ></select>" +
					"</div>" +
				"</div>"+
				"<div class='clear'></div>";
			$(this).after(htmlToAdd).hide();

			
			
			// ELEMENTS
			var allSel = $(this).next().find("select");
			var	leftSel = (o.selectedPosition == 'right') ? allSel.eq(0) : allSel.eq(1);
			var	rightSel = (o.selectedPosition == 'right') ? allSel.eq(1) : allSel.eq(0);

			// MOVE SELECTED OPTION TO RIGHT, NOT SELECTED TO LEFT
			$(this).find("option:selected").clone().appendTo(rightSel);
			$(this).find("option:not(:selected)").clone().appendTo(leftSel);

			// SELECT FIRST LEFT ITEM
			leftSel.find("option").eq(0).attr("selected","selected");

			// ON CHANGE REFRESH ALL BUTTON STATUS
			allSel.change(function() {
				var	div = $(this).parent().parent();
				var	selectSx = leftSel.children();
				var	selectDx = rightSel.children();
				var	selectedSx = leftSel.find("option:selected");
				var	selectedDx = rightSel.find("option:selected");

				if (selectedSx.size() == 0)
					div.find(".AddOne").addClass('ms2side__hide');
				else
					div.find(".AddOne").removeClass('ms2side__hide');
				if (selectedDx.size() == 0)
					div.find(".RemoveOne").addClass('ms2side__hide');
				else
					div.find(".RemoveOne").removeClass('ms2side__hide');

				if (selectSx.size() == 0)
					div.find(".AddAll").addClass('ms2side__hide');
				else {
					if (jQuery().tsort)
						selectSx.tsort();
					div.find(".AddAll").removeClass('ms2side__hide');
					}

				if (selectDx.size() == 0)
					div.find(".RemoveAll").addClass('ms2side__hide');
				else {
					if (jQuery().tsort)
						selectDx.tsort();
					div.find(".RemoveAll").removeClass('ms2side__hide');
				}
			});

			// DOUBLE CLICK ON LEFT SELECT OPTION
			leftSel.dblclick(function () {
				$(this).find("option:selected").each(function(i, selected){
					$(this).remove().appendTo(rightSel);
					el.find("[value=" + $(selected).val() + "]").attr("selected","selected");
				});
				$(this).trigger('change');
			});

			// DOUBLE CLICK ON RIGHT SELECT OPTION
			rightSel.dblclick(function () {
				$(this).find("option:selected").each(function(i, selected){
					$(this).remove().appendTo(leftSel);
					el.find("[value=" + $(selected).val() + "]").attr("selected","");
				});
				$(this).trigger('change');
			});

			// CLICK ON OPTION
			$(this).next().find('div').eq(1).children().click(function () {
				if ($(this).hasClass("AddOne")) {
					leftSel.find("option:selected").each(function(i, selected){
						$(this).remove().appendTo(rightSel);
						el.find("[value=" + $(selected).val() + "]").attr("selected","selected");
					});
				}
				else if ($(this).hasClass("AddAll")) {	// ALL SELECTED
					leftSel.children().appendTo(rightSel);
					leftSel.children().remove();
					el.children().attr("selected","selected");
				}
				else if ($(this).hasClass("RemoveOne")) {
					rightSel.find("option:selected").each(function(i, selected){
						$(this).remove().appendTo(leftSel);
						el.find("[value=" + $(selected).val() + "]").attr("selected","");
					});
				}
				else if ($(this).hasClass("RemoveAll")) {	// ALL REMOVED
					rightSel.children().appendTo(leftSel);
					rightSel.children().remove();
					el.children().attr("selected","");
				}

				leftSel.trigger('change');
			});

			// HOVER ON OPTION
			$(this).next().find('div').eq(1).children().hover(
				function () {
					$(this).addClass('ms2side_hover');
				},
				function () {
					$(this).removeClass('ms2side_hover');
				}
			);

			// UPDATE BUTTON ON START
			leftSel.trigger('change');
			// SHOW WHEN ALL READY
			$(this).next().show();
		});
	};
})(jQuery);