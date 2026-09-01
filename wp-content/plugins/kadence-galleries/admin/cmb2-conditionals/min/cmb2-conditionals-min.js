/**
 * Conditional logic for CMB2 library
 * @author    Awran5 <github.com/awran5>
 * @version   1.0.0
 * @license   under GPL v2.0 (https://github.com/awran5/CMB2-conditional-logic/blob/master/LICENSE)
 * @copyright © 2018 Awran5. All rights reserved.
 * 
 */
!function(e){"use strict";function t(){e("[data-kadence-condition-id]").each(((t,c)=>{function a(e){return o.includes(e)&&""!==e}let n=c.dataset.kadenceConditionId,o=c.dataset.kadenceConditionValue,i=c.closest(".cmb-row"),d;if(i.classList.contains("cmb-repeat-group-field")){let e,t;n=`${i.closest(".cmb-repeatable-group").getAttribute("data-groupid")}[${i.closest(".cmb-repeatable-grouping").getAttribute("data-iterator")}][${n}]`}e('[name="'+n+'"]').each((function(t,c){"select-one"===c.type?(!a(c.value)&&e(i).hide(),e(c).on("change",(function(t){a(t.target.value)?e(i).show():e(i).hide()}))):"radio"===c.type?(!a(c.value)&&c.checked&&e(i).hide(),e(c).on("change",(function(t){a(t.target.value)?e(i).show():e(i).hide()}))):"checkbox"===c.type&&(!c.checked&&e(i).hide(),e(c).on("change",(function(t){t.target.checked?e(i).show():e(i).hide()})))}))}))}t(),e(".cmb2-wrap > .cmb2-metabox").on("cmb2_add_row",(function(){t()}))}(jQuery);