(function (blocks, blockEditor, components, data, element, serverSideRender) {
  'use strict';

  var el = element.createElement;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var Placeholder = components.Placeholder;
  var useSelect = data.useSelect;

  blocks.registerBlockType('assesscraft/assessment', {
    edit: function (props) {
      var records = useSelect(function (select) {
        return select('core').getEntityRecords('postType', 'ac_assessment', { per_page: 100, status: ['publish', 'draft'], orderby: 'title', order: 'asc' });
      }, []);
      var options = [{ label: 'Select an assessment', value: 0 }].concat((records || []).map(function (record) {
        var title = record.title && record.title.rendered ? record.title.rendered.replace(/<[^>]*>/g, '') : 'Untitled assessment';
        return { label: title + (record.status === 'draft' ? ' — Draft' : ''), value: record.id };
      }));
      var control = el(SelectControl, {
        label: 'Assessment',
        value: props.attributes.assessmentId,
        options: options,
        onChange: function (value) { props.setAttributes({ assessmentId: Number(value) }); }
      });
      return el('div', blockEditor.useBlockProps(),
        el(InspectorControls, {}, el(PanelBody, { title: 'Assessment settings', initialOpen: true }, control)),
        props.attributes.assessmentId
          ? el(serverSideRender, { block: 'assesscraft/assessment', attributes: props.attributes })
          : el(Placeholder, { icon: 'chart-bar', label: 'AssessCraft Assessment', instructions: 'Choose an assessment from the block settings.' }, control)
      );
    },
    save: function () { return null; }
  });
}(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.element, window.wp.serverSideRender));

