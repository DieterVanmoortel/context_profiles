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
        appendTo: "body",
        connectToSortable: ".region-droppable",
        revert: "invalid",
        helper: "clone",
        scope: 'region-block'
      });

      $('.region-droppable').droppable({
        accept: '.form-item',
        scope: 'region-block',
        tolerance: "touch",
        drop: function(event, ui) {
          var region = $(this).attr('id');
          ui.draggable.find('input').attr('value', region);
          Drupal.behaviors.contextProfiles.moveBlock(ui.draggable, $(this));
        },
        out: function(event, ui) {
          ui.draggable.find('input').attr('value', '');
        }
      })
    },
    moveBlock: function ( $item , $parent) {
    $item.fadeOut(200, function() {
      $item
        .appendTo( $parent.find('.fieldset-wrapper') )
        .fadeIn();
    });
  }
  };

}(jQuery, Drupal));