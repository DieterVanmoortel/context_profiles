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

      $('.draggable-block').parent().draggable({
        appendTo: "#edit-regions",
        connectToSortable: ".region-droppable",
        revert: "invalid",
        helper: "clone",
        scope: 'region-block'
      }).append('<span class="reset-block">X</span>');

      $('.region-droppable').droppable({
        accept: '.form-item',
        scope: 'region-block',
//        tolerance: "touch",
        hoverClass: "region-active",
        drop: function(event, ui) {
          var region = $(this).attr('id');
          ui.draggable.find('input').attr('value', region);
          Drupal.behaviors.contextProfiles.moveBlock(ui.draggable, $(this).find('.fieldset-wrapper'));
        },
        out: function(event, ui) {
          ui.draggable.find('input').attr('value', '');
        }
      });

      $('.reset-block').on('click', function(e){
        $(this).parent().find('input').attr('value', '');
        var id = $(this).parent().find('.draggable-block').attr('name');
        var parent = $('#edit-disabled').find('#wrap-' + id);
        Drupal.behaviors.contextProfiles.moveBlock($(this).parent(), parent);
      });
    },
    moveBlock: function ( $item , $parent) {
      $item.fadeOut(200, function() {
        $item
          .appendTo( $parent )
          .fadeIn();
      });
    }
  };

}(jQuery, Drupal));