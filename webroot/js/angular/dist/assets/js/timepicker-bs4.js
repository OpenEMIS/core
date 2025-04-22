/**
 * Time picker for Bootstrap 4
 *
 * https://github.com/lesilent/timepicker-bs4
 */
(function () {
//-------------------------------------
'use strict';

/**
 * Array of dayjs format substrings,
 *
 * 0 = used for regex to determine whether format contains unit
 * 1 = format used for buttons and inputs
 * 2 = the unit/view name
 *
 * @type {array}
 */
let FORMATS = [
	['h', 'h', 'hour'],
	['m', 'mm', 'minute'],
	['s', 'ss', 'second'],
	['a', 'A', 'meridiem'],
];

/**
 * Unit lengths
 *
 * @type {object}
 */
const UNIT_LENGTHS = { hour: 24, minute: 60, second: 60, meridiem: 2 };

/**
 *
 * @type {string}
 */
const ACTIVE_CLASS = 'active btn-info';

/**
 *
 * @type {string}
 */
 const INACTIVE_CLASS = 'btn-outline-dark border-white';

/**
 * Flag for whether plugin has been initialized
 *
 * @type {boolean}
 */
let initialized = false;

/**
 * Parse a time string and return a dayjs object
 *
 * @param  {string} str
 * @param  {object} options
 * @return {object|boolean} either a dayjs object or false on error
 */
function parseTime(str, options)
{
	let input_time = false, matches;
	if (typeof str == 'string')
	{
		str = str.replace(/^\s+|\s+$/g, '');
		if ((matches = str.match(/^([0-2]?\d)(?:s*:\s*([0-5]\d))?(?:\s*:\s*([0-5]\d))?(?:\s*([AP])\.?(?:M\.?)?)?$/i))
			&& parseInt(matches[1]) > (matches[4] ? 0 : -1) && parseInt(matches[1]) < (matches[4] ? 13 : 24)
			&& (matches[2] === undefined || (parseInt(matches[2]) > -1 && parseInt(matches[2]) < 60))
			&& (matches[3] === undefined || (parseInt(matches[3]) > -1 && parseInt(matches[3]) < 60)))
		{
			let hour = parseInt(matches[1]);
			if (matches[4])
			{
				hour = hour % 12 + ((matches[4].toUpperCase() == 'P') ? 12 : 0);
			}
			input_time = dayjs().hour(hour).minute(matches[2] == undefined ? 0 : parseInt(matches[2])).second((matches[3] === undefined) ? 0 : parseInt(matches[3]));
		}
		else
		{
			input_time = (options && options.format)
				? dayjs(str, options.format)
				: dayjs(str);
		}
	}
	else
	{
		input_time = dayjs(str);
	}
	return (input_time && input_time.isValid()) ? input_time : false;
}

/**
 * Return allowed unit text object based on min time, max time, and step
 *
 * @param {object} options
 * @return {object}
 */
function getUnitText(options)
{
	const minTime = options.minTime || dayjs().startOf('day');
	const maxTime = options.maxTime || dayjs().endOf('day');
	const step = options.step || 60;
	let valid = { offset: {}, hour: {}, minute: {}, second: {}, meridiem: {}, length: 0 };
	let iTime = minTime.clone();
	const unixOffset = minTime.startOf('day').unix();
	while (iTime.isBefore(maxTime) || iTime.isSame(maxTime, 'second'))
	{
		valid.offset[iTime.unix() - unixOffset] = true;
		valid.hour[iTime.hour()] = true;
		valid.minute[iTime.minute()] = true;
		valid.second[iTime.second()] = true;
		valid.meridiem[(iTime.hour() > 11) ? 1 : 0] = true;
		valid.length++;
		iTime = iTime.add(step, 'second');
	}

	// Convert valid units to arrays
	let unitText = { hour: [], minute: [], second:[], meridiem: [], length: valid.length };
	for (let i = 0; i < 24; i++)
	{
		unitText.hour.push((i in valid.hour)
			? ((i == 0 || i == 12) ? 12 : i % 12)
			: null);
	}
	['minute', 'second'].forEach(function (field) {
		for (let i = 0; i < 60; i++)
		{
			unitText[field].push((i in valid[field]) ? ((i < 10) ? '0' + i : i) : null);
		}
	});
	unitText.meridiem.push((0 in valid.meridiem) ? 'AM' : null);
	unitText.meridiem.push((1 in valid.meridiem) ? 'PM' : null);
/*
	// Craate position arrays
	for (let i = 1; i < 13; i++)
	{
		position.hour[i] = ((i % 12) in valid.hour || (i % 12 + 12) in valid.hour);
		position.minute[i] = (i * 5 % 60) in valid.minute;
		position.second[i] = (i * 5 % 60) in valid.second;
		position.meridiem[i] = (i % 6 > 0) ? (((i < 6) ? 1 : 0) in valid.meridiem) : false;
	}
*/
	return unitText;
}

/**
 * Return whether format string contains a token string
 *
 * This removes escaped characters from format string prior searching for token
 *
 * @param {string} format
 * @param {string} searchElement
 * @return {boolean}
 */
function hasFormat(format, searchElement)
{
	return (format.replace(/\[[^\]]*\]/g).indexOf(searchElement) >= 0);
}

/**
 * Update the view
 *
 * @param {object} $input the input object
 */
function updateView($input)
{
	const prevView = $input.data('prevview');
	const view = $input.data('view') || 'hour';
	const viewTime = $input.data('viewtime');
	const options = $input.data('options');
	const clock_24 = hasFormat(options.format, 'H');
	const step = options.step || 60;
	let submit_disabled = false;
	if (60 % step > 0)
	{
		const minTime = options.minTime || dayjs().startOf('day');
		let viewOffset = viewTime.diff(viewTime.startOf('day'), 'second');
		if (!hasFormat(options.format, 's'))
		{
			viewOffset -= (viewOffset % 60);
		}
		submit_disabled = ((viewOffset - minTime.diff(minTime.startOf('day'), 'second')) % step > 0);
	}
	const input_id = $input.attr('id');
	const $content = jQuery('#' + input_id + '-picker-content').attr('data-view', view);
	$content.find('.submit-btn').prop('disabled', submit_disabled
		|| (options.minTime && viewTime.isBefore(options.minTime, 'second'))
		|| (options.maxTime && viewTime.isAfter(options.maxTime, 'second')));
	let number, position, format;
	switch (view)
	{
		case 'hour':
			number = viewTime.get(view);
			position = (number % 12 > 0) ? (number % 12) : 12;
			format = clock_24 ? 'H' : 'h';
			if (hasFormat(options.format, format + format))
			{
				format += format;
			}
			break;
		case 'minute':
		case 'second':
			number = viewTime.get(view);
			if (number % 5 == 0)
			{
				position = (number > 0) ? (number / 5) : 12;
			}
			format = view.charAt(0).repeat(2);
			break;
		case 'meridiem':
			position = (viewTime.hour() > 11) ? 3 : 9;
			format = 'A';
			break;
		default:
			console.warn('Invalid view ' + view);
			return false;
	}
	const text = viewTime.format(format);
	$content.find('.timepicker-btns button').toggleClass('font-weight-bold', false).filter('[data-unit="' + view + '"]').toggleClass('font-weight-bold', true).text(text);
	$content.find('.clock-input-table .chevron-btn').data('unit', view);

	FORMATS[0][1] = clock_24 ? 'H' : 'h';
	FORMATS.forEach(function (formats) {
		const text = viewTime.format(formats[1]);
		$content.find('.' + formats[2] + '-btn').text(text);
		$content.find('.' + formats[2] + '-input').val(text);
	});

	if (view != prevView)
	{
		$content.find('.clock-input-table button').each(function () {
			const $this = jQuery(this);
			let pos = parseInt($this.attr('class').match(/\bpos\-(\d+)/)[1]);
			let disabled = true;
			if (pos > 0)
			{
				let positions;
				switch (view)
				{
					case 'hour':
						positions = [pos, pos % 12, pos % 12 + 12];
						break;
					case 'minute':
					case 'second':
						positions = [pos % 12 * 5];
						break;
					case 'meridiem':
						positions = (pos % 6 > 0) ? [(pos < 6) ? 1 : 0] : [];
						break;
				}
				positions.forEach(function (pos) {
					disabled &&= (options.unitText[view][pos] === null);
				});
				$this.prop('disabled', disabled).toggleClass('text-light', disabled).toggleClass(INACTIVE_CLASS, pos != position).toggleClass(ACTIVE_CLASS, pos == position);
			}
			else
			{
				$this.text(text);
			}
		});
	}
	$content.data('prevview', view);
	return true;
}

/**
 * Update the clock picker in the popover
 *
 * @param {object} $input the input object
 */
function updatePicker($input)
{
	const input_id = $input.attr('id');
	const options = $input.data('options');
	const now = dayjs();
	const minTime = options.minTime || dayjs().startOf('day');
	const maxTime = options.maxTime || dayjs().endOf('day');
	const step = options.step || 60;
	let validSteps = { hour: {}, minute: {}, second: {}, meridiem: {} };
	let viewTime = $input.data('viewtime');
	let iTime = minTime.clone();
	while (iTime.isBefore(maxTime) || iTime.isSame(maxTime, 'second'))
	{
		validSteps.hour[iTime.hour()] = true;
		validSteps.minute[iTime.minute()] = true;
		validSteps.second[iTime.second()] = true;
		validSteps.meridiem[(iTime.hour() > 11) ? 1 : 0] = true;
		if (!iTime.isBefore(minTime) && !iTime.isAfter(maxTime))
		{
			if (!viewTime && now.isBefore(iTime))
			{
				viewTime = iTime;
			}
		}
		iTime = iTime.add(step, 'second');
	}
	if (!viewTime)
	{
		viewTime = now.endOf(hasFormat(options.format, 's') ? 'second' : 'minute');
	}
	$input.data('viewtime', viewTime);

	// Build html
	const has_second = (hasFormat(options.format, 's') && step % 60 > 0);
	const clock_24 = hasFormat(options.format, 'H');
	const clock_enabled = ((step % 300) == 0) && false;
	const viewHour = viewTime.hour();
	let html = '<div class="clock-input' + (clock_enabled ? '' : ' d-none') + '">'
		+ '<div class="d-flex justify-content-center align-items-center timepicker-btns">'
		+ '<div class="btn-group">'
		+ '<button type="button" class="btn px-2 font-weight-bold hour-btn" data-unit="hour">' + viewTime.format('hh') + '</button>'
		+ '<a class="btn px-0 disabled" href="javascript:void(0)" role="button" aria-disabled="true">:</a>'
		+ '<button type="button" class="btn px-2 minute-btn" data-unit="minute"' + + ((step % 3600 > 0) ? '' : ' disabled="disabled"') + '>' + viewTime.format('mm') + '</button>'
		+ (has_second ? '<a class="btn px-0 disabled" href="javascript:void(0)" role="button" aria-disabled="true">:</a><button type="button" id="' + input_id + '-picker-second-btn" class="btn px-2 second-btn" data-unit="second">' + viewTime.format('ss') + '</button>' : '')
		+ (clock_24 ? '<button type="button" class="btn px-2 meridiem-btn" data-unit="meridiem">' + viewTime.format('A') + '</button>' : '')
		+ '</div></div>'
		+ ('<table class="clock-input-table table table-sm table-borderless timepicker-table mx-auto w-auto mb-1">'
		+ '<thead class="thead-light"><tr><th class="text-center py-1" colspan="5"><span class="hour">Hour</span><span class="minute">Minute</span><span class="second">Second</span><span class="meridiem">Meridiem</span></th></tr></thead>'
		+ '<tbody>'
		+ '<tr>'
		+ '<td></td>'
		+ '<td class="text-right">{{11}}</td>'
		+ '<td class="text-center">{{12}}</td>'
		+ '<td class="text-left">{{1}}</td>'
		+ '<td></td>'
		+ '</tr><tr>'
		+ '<td class="text-right">{{10}}</td>'
		+ '<td class="text-center align-bottom" colspan="3"><a id="' + input_id + '-picker-add-link" class="btn btn-link px-1 mx-0 chevron-btn" data-step="1" href="javascript:void(0)"><i class="fas fa-chevron-up fa-lg"></i></a></td>'
		+ '<td class="text-left">{{2}}</td>'
		+ '</tr><tr>'
		+ '<td class="text-right">{{9}}</td>'
		+ '<td class="text-center" colspan="3">{{0}}</td>'
		+ '<td class="text-left">{{3}}</td>'
		+ '</tr><tr>'
		+ '<td class="text-right">{{8}}</td>'
		+ '<td class="text-center" colspan="3"><a id="' + input_id + '-picker-sub-link" class="btn btn-link px-1 mx-0 chevron-btn" data-step="-1" href="javascript:void(0)"><i class="fas fa-chevron-down fa-lg"></i></a></td>'
		+ '<td class="text-left">{{4}}</td>'
		+ '</tr><tr>'
		+ '<td></td>'
		+ '<td class="text-right">{{7}}</td>'
		+ '<td class="text-center">{{6}}</td>'
		+ '<td class="text-left">{{5}}</td>'
		+ '<td></td>'
		+ '</tr></tbody></table>').replace(/{{(\w+)}}/g, function (match, position) {
			if (position == 0)
			{
				return '<button type="button" id="'+ input_id + '-picker-center-btn" class="btn btn-outline-dark border-white font-weight-bold pos-0" data-hour="' + viewHour + '"'
					+ (viewHour in validSteps.hour ? '' : ' disabled="disabled"')
					+ '>' + ((viewHour % 12 > 0) ? viewHour % 12 : 12) + '</button>';
			}

			const pos_hour = position % 12;
			const pos_minute = pos_hour * 5;

			const meridiem_class = (position % 3 > 0) ? ' text-light' : '';
			const meridiem_text = (position % 6 > 0) ? ((position < 6) ? 'PM' : 'AM') : '&nbsp;';
			return '<button type="button" class="btn px-1 '
				+ ((viewHour % 12 == position % 12) ? 'active btn-info' : 'btn-outline-dark border-white')
				+ ' pos-' + position + '" '
				+ (pos_hour in validSteps.hour ? '' : ' disabled="disabled"')
				+ '><span class="hour">' + position + '</span><span class="minute second">' + (pos_minute > 9 ? '' : '0') + pos_minute + '</span><span class="meridiem' + meridiem_class + '">' + meridiem_text
				+ '</span></button>';
		})
		+ '</div><div class="keyboard-input' + (clock_enabled ? ' d-none' : '') + '"><table class="mx-auto text-center"><tr>'
		+ '<td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="hour" data-step="1" href="javascript:void(0)"><i class="fas fa-chevron-up fa-lg"></i></a></td>'
		+ '<td></td>'
		+ '<td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="minute" data-step="1" href="javascript:void(0)"><i class="fas fa-chevron-up fa-lg"></i></a></td>'
		+ (has_second ? '<td></td><td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="second" data-step="1" href="javascript:void(0)"><i class="fas fa-chevron-up fa-lg"></i></a></td>' : '')
		+ (clock_24 ? '' : '<td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="meridiem" data-step="1" href="javascript:void(0)"><i class="fas fa-chevron-up fa-lg"></i></a></td>')
		+ '</tr><tr>'
		+ '<td><input type="text" class="form-control text-center border-light hour-input" minlength="1" maxlength="2" inputmode="numeric" /></td><td>:</td><td><input type="text" class="form-control text-center border-light minute-input" minlength="1" maxlength="2" inputmode="numeric" /></td>'
		+ (has_second ? '<td>:</td><td><input type="text" class="form-control text-center border-light second-input" minlength="1" maxlength="2" inputmode="numeric" /></td>' : '')
		+ (clock_24 ? '' : '<td><button type="button" class="btn meridiem-btn border-light"></button></td>')
		+ '</tr><tr>'
		+ '<td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="hour" data-step="-1" href="javascript:void(0)"><i class="fas fa-chevron-down fa-lg"></i></a></td>'
		+ '<td></td>'
		+ '<td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="minute" data-step="-1" href="javascript:void(0)"><i class="fas fa-chevron-down fa-lg"></i></a></td>'
		+ (has_second ? '<td></td><td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="second" data-step="-1" href="javascript:void(0)"><i class="fas fa-chevron-down fa-lg"></i></a></td>' : '')
		+ (clock_24 ? '' : '<td><a class="btn btn-link px-1 mx-0 chevron-btn" data-unit="meridiem" data-step="-1" href="javascript:void(0)"><i class="fas fa-chevron-down fa-lg"></i></a></td>')
		+ '</tr></table></div>'
		+ '<div class="d-flex justify-content-between">'
		+ '<div class="invisible"><button type="button" class="btn btn-link input-toggle-btn' + (clock_enabled ? ' d-none' : '') + '" data-input="clock"><i class="far fa-clock fa-fw"></i></button><button type="button" class="btn btn-link input-toggle-btn' + (clock_enabled ? '' : ' d-none') + '" data-input="keyboard"><i class="far fa-keyboard fa-fw"></i></button></div>'
		+ '<div><button type="button" class="btn btn-secondary mx-1 cancel-btn" data-dismiss="popover" title="Cancel"><i class="fas fa-times fa-fw"></i></button><button type="button" class="btn btn-primary mx-1 submit-btn" title="OK"><i class="fas fa-check fa-fw"></i></button></div>'
		+ '</div>';

	const $content = jQuery('#' + input_id + '-picker-content');
	const $table = $content.html(html).find('.clock-input-table');
	const $center_btn = jQuery('#' + input_id + '-picker-center-btn');
	$content.parents('.timepicker-popover').attr('data-theme', (options.theme == 'auto') ? ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : '') : options.theme);
	$content.find('.timepicker-btns button').on('click', function () {
		const unit = jQuery(this).blur().data('unit');
		$content.find('.clock-input-table .chevron-btn').data('unit', unit);
		updateView($input.data('view', unit));
	});
	const $hour_input = $content.find('.hour-input').on('change', function () {
		let hour = this.value.replace(/\D+/, '');
		if (hour.length > 0)
		{
			hour = parseInt(hour);
			if (hour > -1 && hour < 24 && hour in options.unitText.hour)
			{
				$input.data('viewtime', $input.data('viewtime').hour(hour));
			}
		}
		updateView($input);
	});
	$hour_input.add($content.find('.minute-input, .second-input').on('change', function () {
		let number = this.value.replace(/\D+/, '');
		if (number.length > 0)
		{
			number = parseInt(number);
			const unit = jQuery(this).attr('class').match(/(minute|second)\-input/)[1];
			if (number > -1 && number < 60 && number in options.unitText[unit])
			{
				$input.data('viewtime', $input.data('viewtime').set(unit, number));
			}
		}
		updateView($input);
	})).on('keyup', function (event) {
		if (event.key == 'Enter')
		{
			$content.find('.submit-btn').triggerHandler('click');
		}
	});

	$content.find('.meridiem-btn').on('click', function () {
		$input.data('viewtime', $input.data('viewtime').hour(($input.data('viewtime').hour() + 12) % 24));
		updateView($input);
	}).on('keydown', function (event) {
		const key = event.key.toUpperCase();
		const hour = $input.data('viewtime').hour();
		let offset = 0;
		if (key == 'A' && hour > 12)
		{
			offset = -12;
		}
		else if (key == 'P' && hour < 11)
		{
			offset = 12;
		}
		if (offset != 0)
		{
			$input.data('viewtime', $input.data('viewtime').hour(hour + offset));
			updateView($input);
		}
	});
	$content.find('.input-toggle-btn').attr('tabindex', -1).on('click', function () {
		const input = jQuery(this).data('input');
		$content.find('.clock-input').toggleClass('d-none', input != 'clock');
		$content.find('.keyboard-input').toggleClass('d-none', input != 'keyboard');
		$content.find('.input-toggle-btn').each(function () {
			const $this = jQuery(this);
			$this.toggleClass('d-none', $this.data('input') == input);
		});
		$input.popover('update');
		$hour_input.select();
	});
	$content.find('.cancel-btn').on('click', function () {
		$input.popover('hide');
	});
	$content.find('.submit-btn').on('click', function () {
		$input.val($input.data('viewtime').format($input.data('options').format)).popover('hide').data('view', null).trigger('change');
	});
	$table.find('button').on('click', function () {
		const $this = jQuery(this);
		let viewTime = $input.data('viewtime');
		let position = $this.attr('class').match(/\bpos\-(\d+)/)[1];
		const view = $input.data('view') || 'hour';
		switch (view)
		{
			case 'hour':
				if (position > 0)
				{
					viewTime = viewTime.hour(position % 12 + ((viewTime.hour() > 11) ? 12 : 0));
				}
				break;
			case 'minute':
				if (position > 0)
				{
					viewTime = viewTime.minute(position % 12 * 5);
				}
				break;
			case 'second':
				if (position > 0)
				{
					viewTime = viewTime.second(position % 12 * 5);
				}
				break;
			case 'meridiem':
				if (position > 0)
				{
					viewTime = viewTime.hour(viewTime.hour() % 12 + ((position < 6) ? 12 : 0));
				}
				break;
		}
		$input.data('viewtime', viewTime);
		let picked = true;
		FORMATS.forEach(function (formats) {
			const regex = new RegExp(formats[0], 'i');
			if (picked && regex.test(options.format))
			{
				position = null;
				let number;
				const nextUnit = formats[2];
				switch (nextUnit)
				{
					case 'hour':
						number = viewTime.get(nextUnit) % 12;
						position = (number > 0) ? number : 12;
						break;
					case 'minute':
					case 'second':
						number = viewTime.get(nextUnit);
						if (number % 5 == 0)
						{
							position = (number > 0) ? (number / 5) : 12;
						}
						break;
					case 'meridiem':
						position = (viewTime.hour() > 11) ? 3 : 9;
						break;
				}
				$input.data('view', nextUnit);
				picked = false;
			}
		});

		if (picked)
		{
			$input.val(viewTime.format(options.format)).popover('hide').data('view', null).trigger('change');
		}
		else
		{
			updateView($input);
		}
	});
	$content.find('.chevron-btn').attr('tabindex', -1).on('click', function () {
		const $this = jQuery(this).blur();
		const options = $input.data('options');
		const unit = $this.data('unit') || 'hour';
		const step = $this.data('step');
		const viewTime = $input.data('viewtime');
		const number = (unit == 'meridiem')
			? ((viewTime.hour() > 11) ? 1 : 0)
			: viewTime.get(unit);
		if (!(number in options.unitText[unit]))
		{
			// Set to number
		}
		if (options.unitText[unit].length < 2)
		{
			return;
		}

		const unitLength = UNIT_LENGTHS[unit];
		let idx = number;
		switch (unit)
		{
			case 'hour':
			case 'minute':
			case 'second':
				do
				{
					idx = (idx + step + unitLength) % unitLength;
					if (options.unitText[unit][idx] !== null)
					{
						$center_btn.text(options.unitText[unit][idx]);
						$input.data('viewtime', viewTime.set(unit, idx));
						break;
					}
				}
				while (idx != number);
				break;
			case 'meridiem':
				idx = (idx + step + unitLength) % unitLength;
				if (options.unitText[unit][idx] !== null)
				{
					$center_btn.text(options.unitText[unit][idx]);
					$input.data('viewtime', viewTime.hour(viewTime.hour() % 12 + ((idx > 0) ? 12 : 0)));
				}
				break;
		}
		updateView($input);
	});
	updateView($input);
}

/**
 * Add method for initializing plugin
 */
jQuery.fn.timepicker = function (options) {
	// Get boostrap version
	const bs_version = parseInt(((typeof bootstrap == 'object') ? bootstrap.Dropdown.VERSION : jQuery.fn.dropdown.Constructor.VERSION || '0').replace(/\..+$/, ''));
	if (bs_version < 4)
	{
		console.error('Invalid bootstrap version ' + bs_version + ' detected');
	}

	// Handle functions
	if (typeof options == 'string')
	{
		if (this.length < 1)
		{
			return undefined;
		}
		let input_options = this.data('options') || {};
		const single_arg = (arguments.length == 1);
		switch (options)
		{
			case 'format':
				if (single_arg)
				{
					return input_options.format;
				}
				else if (arguments[1] && typeof arguments[1] == 'string')
				{
					input_options.format = arguments[1];
					this.data('options', input_options);
				}
				else
				{
					console.warn('Invalid format');
				}
				break;
			case 'minTime':
			case 'maxTime':
				if (single_arg)
				{
					return input_options[options];
				}
				else if (arguments[1])
				{
					const newTime = parseTime(arguments[1]);
					if (newTime && newTime.isValid())
					{
						input_options[options] = newTime;
						input_options.unitText = getUnitText(input_options);
						this.data('options', input_options);
					}
					else
					{
						console.warn('Invalid ' + options);
					}
				}
				else
				{
					input_options[options] = null;
					input_options.unitText = getUnitText(input_options);
					this.data('options', input_options);
				}
				break;
			case 'step':
				if (single_arg)
				{
					return input_options[options];
				}
				else if (arguments[1])
				{
					const step = parseInt(arguments[1]);
					if (step > 0 && step < 86400
						&& step % (hasFormat(input_options.format, 's') ? 1 : 60) == 0)
					{
						input_options.step = step;
						input_options.unitText = getUnitText(input_options);
						this.data('options', input_options);
					}
					else
					{
						console.warn('Invalid ' + options);
					}
				}
				else
				{
					input_options.step = 60;
					input_options.unitText = getUnitText(input_options);
					this.data('options', input_options);
				}
				break;
			case 'theme':
				if (single_arg)
				{
					return input_options.theme;
				}
				else if (arguments[1] === null || typeof arguments[1] == 'string')
				{
					input_options.theme = arguments[1];
					this.data('options', input_options);
				}
				else
				{
					console.warn('Invalid theme');
				}
				break;
			case 'time':
				if (single_arg)
				{
					return parseTime(this.val(), input_options) || null;
				}
				else
				{
					const newTime = (arguments[1]) ? parseTime(arguments[1], input_options) : null;
					this.val((newTime && newTime.isValid()) ? newTime.format(input_options.format) : '');
				}
				break;
			case 'viewTime':
				if (single_arg)
				{
					return this.data('viewtime');
				}
				else
				{
					const newTime = (arguments[1]) ? parseTime(arguments[1], input_options) : null;
					this.data('viewtime', newTime);
				}
				break;
			case 'view':
				if (single_arg)
				{
					return this.data('view');
				}
				else
				{
					const view = arguments[1];
					updateView(jQuery(this).data('view', view));
				}
				break;
			default:
				break;
		}
		return this;
	}

	// Initialize code if it hasn't already
	if (!initialized)
	{
		initialized = true;
		let table_class = '.timepicker-table ';
		jQuery(document.head).append('<style id="timepicker-style">'
			+ '.timepicker-popover { font-size: inherit;  }'
			+ '.timepicker-popover .btn-link:hover { background-color: #e2e6ea; }'
			+ '.timepicker-popover .meridiem-btn { min-width: 3.2rem; }'
			+ '.timepicker-popover .meridiem-btn:hover { box-shadow: 0 0 0 .2rem rgba(0,123,255,.25); }'
			+ '.timepicker-popover input { width: 3rem; }'
			+ '.timepicker-btns .btn:hover { background-color: #e2e6ea; color: #000; }'
			+ '.timepicker-content span.hour, .timepicker-content span.minute, .timepicker-content span.second, .timepicker-content span.meridiem { display: none; }'
			+ '.timepicker-content[data-view="hour"] span.hour,'
			+ '.timepicker-content[data-view="minute"] span.minute,'
			+ '.timepicker-content[data-view="second"] span.second,'
			+ '.timepicker-content[data-view="meridiem"] span.meridiem { display: inline; }'
			+ table_class + 'td button:focus { box-shadow: none !important; }'
			+ table_class + 'td button:not(:disabled):hover { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff; }'
			+ table_class + 'td button:disabled { cursor: not-allowed; }'
			+ table_class + 'td button.today { background-color: #fcf8e3; }'
			+ table_class + 'td, .timepicker-table th { padding: 0; }'
			+ table_class + 'button.btn { min-width: 2.3rem; }'
			+ table_class + 'button:focus { box-shadow: none !important; }'
			+ table_class + 'button:not(:disabled):hover { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff; }'
			+ table_class + 'td button.pos-1 { margin: .6rem 0 0 .4rem; }'
			+ table_class + 'td button.pos-2 { margin: 0 .7rem .3rem 0; }'
			+ table_class + 'td button.pos-3 { margin: 0 0 0 .6rem; }'
			+ table_class + 'td button.pos-4 { margin: .3rem .7rem 0 0; }'
			+ table_class + 'td button.pos-5 { margin: 0 0 .6rem .4rem; }'
			+ table_class + 'td button.pos-6 { margin: .8rem 0 0 0; }'
			+ table_class + 'td button.pos-7 { margin: 0 .4rem .6rem 0; }'
			+ table_class + 'td button.pos-8 { margin: .3rem 0 0 .7rem; }'
			+ table_class + 'td button.pos-9 { margin: 0 .6rem 0 0; }'
			+ table_class + 'td button.pos-10 { margin: 0 0 .3rem .7rem; }'
			+ table_class + 'td button.pos-11 { margin: .6rem .4rem 0 0; }'
			+ table_class + 'td button.pos-12 { margin: 0 0 .6rem 0; }'
			+ '.timepicker-popover[data-theme="dark"] { background-color: #000000; border-color: #ffffff; color: #dee2e6; }'
			+ '.timepicker-popover[data-theme="dark"] .popover-header { background-color: #343a40; color: #ffffff; }'
			+ '.timepicker-popover[data-theme="dark"] .popover-header .close { filter: invert(1) grayscale(1) brightness(2); }'
			+ '.timepicker-popover[data-theme="dark"] .border-light { background-color: #000000; border-color: #6c757d !important; color: #ffffff; }'
			+ '.timepicker-popover[data-theme="dark"] input.border-light:focus { background-color: inherit; border-color: #86b7fe !important; color: #ffffff; }'
			+ '.timepicker-popover[data-theme="dark"] .meridiem-btn { color: #ffffff; }'
			+ '.timepicker-popover[data-theme="dark"] .meridiem-btn:hover { border-color: #86b7fe !important; }'
			+ '</style>');

		// Make popovers close when clicked outside of them
		jQuery(document.body).on('mouseup', function (e) {
			if (jQuery(e.target).parents('.popover').length == 0)
			{
				jQuery('.timepicker').popover('hide');
			}
		});
	}

	// Process options
	if (typeof options == 'undefined')
	{
		options = {};
	}
	const common_options = jQuery.extend({}, jQuery.fn.timepicker.defaults, options);
	['minTime', 'maxTime'].forEach(function (option) {
		if (common_options[option])
		{
			common_options[option] = parseTime(common_options[option]);
		}
	});

	// Initialize the inputs
	return this.each(function () {
		const $input = jQuery(this);

		// Process options
		let input_options = jQuery.extend(true, {}, common_options);
		let format = $input.data('format') || common_options.format;
		if (format)
		{
			input_options.format = format;
		}
		let minTime = $input.attr('min') || $input.data('mintime') || common_options.minTime;
		if (minTime && (minTime = parseTime(minTime)) && minTime.isValid())
		{
			input_options.minTime = minTime;
		}
		let maxTime = $input.attr('max') || $input.data('maxtime') || common_options.maxTime;
		if (maxTime && (maxTime = parseTime(maxTime)) && maxTime.isValid())
		{
			input_options.maxTime = maxTime;
		}
		const step = $input.attr('step') || $input.data('step') || common_options.step;
		if (step > 0 && step < 86400 && 60 % step > 0)
		{
			input_options.step = parseInt(step);
		}
		const theme = $input.data('theme') || common_options.theme;
		if (theme)
		{
			input_options.theme = theme;
		}
		input_options.unitText = getUnitText(input_options);
		$input.data('options', input_options);
		if ($input.data('timepicker'))
		{
			// If timepicker is already initialized, then return
			return this;
		}
		$input.data('timepicker', true);

		let input_id = this.id;
		let $toggles = $input.siblings().find('[data-toggle="timepicker"]:not([data-target])');
		if (this.id)
		{
			$toggles = $toggles.add('[data-toggle="timepicker"][data-target="#' + this.id + '"]');
		}
		else
		{
			input_id = 'input-' + Math.floor(Math.random() * 1000000 + 1);
			this.id = input_id;
		}
		$input.addClass('timepicker');

		const $label = jQuery('label[for="' + input_id + '"]');
		$input.on('change', function () {
			this.value = this.value.replace(/^\s+|\s+$/g, '');
			const options = $input.data('options');
			const newTime = parseTime(this.value, options);
			this.value = (newTime !== false) ? newTime.format(options.format) : '';
		}).on('shown.bs.popover', function () {
			if (window.screen.width > 575)
			{
				jQuery('#' + input_id + '-picker-content').find('.hour-input').select();
			}
		}).on('inserted.bs.popover', function () {
			jQuery('.popover').find('[data-dismiss="popover"]').on('click', function () {
				$input.popover('hide');
			});
			updatePicker($input, input_options);
		}).on('hide.bs.popover', function () {
			$input.data('view', null);
		}).popover({
			html: true,
			placement: 'bottom',
			sanitize: false,
			title: '<button class="close mt-n1" data-dismiss="popover">&times;</button>' + (($label.length > 0) ? $label.html() : 'Time'),
			template: '<div id="' + input_id + '-picker-popover" class="popover timepicker-popover bs-popover-bottom" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div id="' + input_id + '-popover-body" class="popover-body border-bottom"></div><div class="popover-footer bg-light text-right px-3 py-2 rounded-lg" hidden="hidden"><button type="button" class="btn btn-secondary btn-sm" title="Close the picker" data-dismiss="popover"><i class="fas fa-times"></i> Close</button></div></div>',
			trigger: (($toggles.length > 0) ? 'manual' : 'click'),
			popperConfig: {
/*
				modifiers: {
					hide: {
						enabled: false
					},
					preventOverflow: {
						enabled: false,
//						boundariesElement: 'window',
						escapeWithReference: true
					}
				},
//				positionFixed: true
*/
			},
			content: function () {
				const options = $input.data('options');
				const viewTime = parseTime($input.val(), options);
				$input.data('viewtime', viewTime);
				return '<div id="' + input_id + '-picker-content" class="timepicker-content" data-view="hour"></div>';
			}
		});

		$toggles.on('click', function () {
			$input.popover('toggle');
			this.blur();
		});
	});
};

/**
 * Default options
 *
 * @type {object}
 * @todo add support for additional options
 */
jQuery.fn.timepicker.defaults = {
	format: 'hh:mm A',
	maxTime: null,
	minTime: null,
	step: 60,
	theme: 'light'
};

/*
 * Initialize timepickers
 */
document.addEventListener('DOMContentLoaded', function() {
	jQuery('[data-toggle="timepicker"][data-target]').each(function () {
		jQuery(jQuery(this).data('target')).timepicker();
	});
});

//-------------------------------------
}());
