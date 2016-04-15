/**
 * @file
 * Block admin behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Highlights the block that was just placed into the block listing.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block placement highlighting.
   */
  Drupal.behaviors.contextProfiles = {
    attach: function (context, settings) {

      $('.draggable-block').draggable({
        appendTo: '#edit-regions',
        connectToSortable: '.region-droppable',
        revert: 'invalid',
        stack: '.draggable-block',
        scope: 'region-block',
        stop: function (event, ui) {
          var region = $(this).parents('.region-droppable');
          Drupal.behaviors.contextProfiles.adjustBlockWeights(region);
        }
      });

      $('.region-droppable').droppable({
        addClasses: false,
        tolerance: 'pointer',
        accept: '.draggable-block',
        scope: 'region-block',
        hoverClass: 'region-active',
        drop: function (event, ui) {
          var region = $(this).attr('id');
          ui.draggable.find('.block-region').attr('value', region);
        },
        out: function (event, ui) {
          ui.draggable.find('.block-region').attr('value', '');
        }
      }).sortable({
        items: '.block-form',
        cancel: '.sortable-block'
      });

      $('.reset-block').on('click', function (e) {
        $(this).parent().find('input').attr('value', '');
        var plugin = $(this).parent().attr('plugin');
        var parent = $('#edit-disabled').find('#wrap-' + plugin);
        Drupal.behaviors.contextProfiles.moveBlock($(this).parent(), parent);
      });

      $('#edit-block-lookup').keyup(function () {
        var lookup = $('#edit-block-lookup').val().toLowerCase();
        $.each($('#edit-disabled').find('.block-form'), function () {
          if ($(this).find('label').html().toLowerCase().search(lookup) > -1) {
            $(this).fadeIn();
          }
          else {
            $(this).fadeOut();
          }
        });
      });

    },
    moveBlock: function ($item, $parent) {
      $item.fadeOut(200, function () {
        $item
          .appendTo($parent)
          .fadeIn();
      });
    },
    adjustBlockWeights: function (region) {
      var index = -10;
      region.find('.block-weight').each(function () {
        $(this).val(index);
        index++;
      });
    }
  };

}(jQuery, Drupal));
