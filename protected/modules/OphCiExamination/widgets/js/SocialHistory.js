

var OpenEyes = OpenEyes || {};

OpenEyes.OphCiExamination = OpenEyes.OphCiExamination || {};

(function (exports) {
    function SocialHistoryController(options) {
      this.options = $.extend(true, {}, SocialHistoryController._defaultOptions, options);
      this.$tableSelector = $( '#'+this.options.modelName+'_entry_table');
      this.$popupSelector = $('#add-to-social-history');

      this.initialiseTriggers();

    }

    SocialHistoryController._defaultOptions = {
      modelName: 'OEModule_OphCiExamination_models_SocialHistory'
    };

    
    SocialHistoryController.prototype.initialiseTriggers = function () {

      $('#OEModule_OphCiExamination_models_SocialHistory_occupation_id').on('change', function() {
        if ($('#OEModule_OphCiExamination_models_SocialHistory_occupation_id option:selected').attr('value') == 7/*Other*/) {
          $('#div_OEModule_OphCiExamination_models_SocialHistory_type_of_job').show();
        } else {
          $('#div_OEModule_OphCiExamination_models_SocialHistory_type_of_job').hide();
          $('#OEModule_OphCiExamination_models_SocialHistory_type_of_job').val('');
        }
      });

      var select_lists = ['occupation', 'alcohol', 'smoking_status', 'accommodation'];
      for (i in select_lists){
        $('#add-to-social-history ul.'+select_lists[i]+' li').on('click', function (e) {
          $(this).siblings('.selected').removeClass('selected');
        })
      }

    };

    SocialHistoryController.prototype.addEntry = function (selectedItems) {

      for (var i in selectedItems) {
        var item = selectedItems[i];
        var itemSetId = item['itemSet'].options['id'];
        var $field = this.$tableSelector.find('#' + this.options.modelName + '_' + itemSetId);
        $field.val(item['id']);
        $field.change();
      }

      this.$popupSelector.find('.selected').removeClass('selected');

    };

  exports.SocialHistoryController = SocialHistoryController;
})(OpenEyes.OphCiExamination);