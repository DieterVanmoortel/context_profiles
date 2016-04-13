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
        appendTo: "#edit-regions",
        connectToSortable: ".region-droppable",
        revert: "invalid",
        stack: ".draggable-block",
        scope: 'region-block',
        stop: function( event, ui ) {
          var region = $(this).parents('.region-droppable');
          Drupal.behaviors.contextProfiles.adjustBlockWeights(region);
        }
      });

      $('.sortable-block').draggable({
        appendTo: "#edit-regions",
        connectToSortable: ".region-droppable",
        containment: "parent",
        stop: function( event, ui ) {
          var region = $(this).parents('.region-droppable');
          Drupal.behaviors.contextProfiles.adjustBlockWeights(region);
        }
      });

      $('.region-disabled').sortable({
        items: ".block-form"
      });

      $('.region-droppable').droppable({
        addClasses: false,
        tolerance: "pointer",
        accept: '.draggable-block',
        scope: 'region-block',
        hoverClass: "region-active",
        drop: function(event, ui) {
          var region = $(this).attr('id');
          ui.draggable.find('.block-region').attr('value', region);
        },
        out: function(event, ui) {
          ui.draggable.find('.block-region').attr('value', '');
        }
      }).sortable({
        items: ".block-form"
      });

      $('.reset-block').on('click', function(e) {
        $(this).parent().find('input').attr('value', '');
        var plugin = $(this).parent().attr('plugin');
        var parent = $('#edit-disabled').find('#wrap-' + plugin);
        Drupal.behaviors.contextProfiles.moveBlock($(this).parent(), parent);
      });
    },
    moveBlock: function ( $item , $parent) {
      $item.fadeOut(200, function() {
        $item
          .appendTo( $parent )
          .fadeIn();
      });
    },
    adjustBlockWeights: function( region ) {
      var index = -10;
      region.find('.block-weight').each(function() {
        $(this).val(index);
        index++;
      })
    }
  };

}(jQuery, Drupal));
