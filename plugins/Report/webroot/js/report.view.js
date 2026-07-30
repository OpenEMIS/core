var ReportView = {
	config: null,
	sectionIndex: 0,
	page: 1,
	loading: false,

	init: function () {
		var $container = $('#report-lazy-view');
		if (!$container.length) {
			return;
		}

		var configJson = $container.attr('data-config');
		if (!configJson) {
			return;
		}

		try {
			ReportView.config = JSON.parse(configJson);
		} catch (e) {
			return;
		}

		ReportView.renderSectionPagination();
		ReportView.renderRowPagination();
		ReportView.bindEvents();
		ReportView.renderInitialTable();
	},

	renderInitialTable: function () {
		var $table = $('#lazyReportTable');
		var headers = [];
		$table.find('thead th').each(function () {
			headers.push($(this).text());
		});

		if (headers.length) {
			ReportView.updateRowInfo();
			return;
		}

		ReportView.loadPage(ReportView.sectionIndex, ReportView.page);
	},

	bindEvents: function () {
		$('#report-section-pagination').on('click', '.page-number', function (e) {
			e.preventDefault();
			var targetIndex = parseInt($(this).attr('data-section-index'), 10);
			if (isNaN(targetIndex) || targetIndex === ReportView.sectionIndex) {
				return;
			}
			ReportView.sectionIndex = targetIndex;
			ReportView.page = 1;
			ReportView.loadPage(ReportView.sectionIndex, ReportView.page);
		});

		$('#report-section-pagination').on('click', '.previous:not(.disabled)', function (e) {
			e.preventDefault();
			if (ReportView.sectionIndex > 0) {
				ReportView.sectionIndex -= 1;
				ReportView.page = 1;
				ReportView.loadPage(ReportView.sectionIndex, ReportView.page);
			}
		});

		$('#report-section-pagination').on('click', '.next:not(.disabled)', function (e) {
			e.preventDefault();
			var sections = ReportView.config.sections || [];
			if (ReportView.sectionIndex < sections.length - 1) {
				ReportView.sectionIndex += 1;
				ReportView.page = 1;
				ReportView.loadPage(ReportView.sectionIndex, ReportView.page);
			}
		});

		$('#report-row-pagination').on('click', '.previous:not(.disabled)', function (e) {
			e.preventDefault();
			if (ReportView.page > 1) {
				ReportView.page -= 1;
				ReportView.loadPage(ReportView.sectionIndex, ReportView.page);
			}
		});

		$('#report-row-pagination').on('click', '.next:not(.disabled)', function (e) {
			e.preventDefault();
			var totalPages = ReportView.getTotalPages();
			if (ReportView.page < totalPages) {
				ReportView.page += 1;
				ReportView.loadPage(ReportView.sectionIndex, ReportView.page);
			}
		});
	},

	getTotalPages: function () {
		var sections = ReportView.config.sections || [];
		var section = sections[ReportView.sectionIndex] || {};
		var totalRows = parseInt(section.totalRows, 10) || 0;
		var pageSize = parseInt(ReportView.config.pageSize, 10) || 50;

		return Math.max(1, Math.ceil(totalRows / pageSize));
	},

	loadPage: function (sectionIndex, page) {
		if (ReportView.loading) {
			return;
		}

		var sections = ReportView.config.sections || [];
		var section = sections[sectionIndex] || {};
		ReportView.loading = true;
		$('#report-lazy-loading').show();

		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: ReportView.config.ajaxUrl,
			data: {
				file_path: ReportView.config.filePath,
				module: ReportView.config.module,
				section_index: sectionIndex,
				section_title: section.title || '',
				page: page
			},
			success: function (response) {
				if (!response || !response.success || !response.data) {
					return;
				}

				var data = response.data;
				ReportView.renderTable(data.headers || [], data.rows || []);
				if (sections[sectionIndex]) {
					sections[sectionIndex].totalRows = data.totalRows;
				}
				ReportView.page = data.page || page;
				ReportView.renderSectionPagination();
				ReportView.renderRowPagination();
			},
			complete: function () {
				ReportView.loading = false;
				$('#report-lazy-loading').hide();
			}
		});
	},

	renderTable: function (headers, rows) {
		var $head = $('#lazyReportHead');
		var $body = $('#lazyReportBody');
		$head.empty();
		$body.empty();

		$.each(headers, function (_, header) {
			$head.append($('<th>').text(header));
		});

		if (!rows.length) {
			$body.append(
				$('<tr>').append(
					$('<td>').attr('colspan', Math.max(headers.length, 1)).text('-')
				)
			);
			return;
		}

		$.each(rows, function (_, row) {
			var $tr = $('<tr>');
			$.each(row, function (_, value) {
				$tr.append($('<td>').text(value === null || value === undefined ? '' : value));
			});
			$body.append($tr);
		});
	},

	renderSectionPagination: function () {
		var sections = ReportView.config.sections || [];
		var $pagination = $('#report-section-pagination');
		if (sections.length <= 1) {
			$pagination.hide();
			return;
		}

		$pagination.show();
		var $pages = $pagination.find('.section-pages');
		$pages.empty();

		$.each(sections, function (index, section) {
			var $link = $('<a>')
				.addClass('paginate_button page-number')
				.attr('href', '#')
				.attr('data-section-index', index)
				.text(section.title || (index + 1));

			if (index === ReportView.sectionIndex) {
				$link.addClass('current');
			}

			$pages.append($link);
		});

		$pagination.find('.previous').toggleClass('disabled', ReportView.sectionIndex === 0);
		$pagination.find('.next').toggleClass('disabled', ReportView.sectionIndex >= sections.length - 1);
		$('#report-section-info').text((ReportView.sectionIndex + 1) + ' / ' + sections.length);
	},

	renderRowPagination: function () {
		var totalPages = ReportView.getTotalPages();
		var sections = ReportView.config.sections || [];
		var section = sections[ReportView.sectionIndex] || {};
		var totalRows = parseInt(section.totalRows, 10) || 0;
		var pageSize = parseInt(ReportView.config.pageSize, 10) || 50;

		if (totalRows <= pageSize) {
			$('#report-row-pagination').hide();
			$('#report-row-info').text('');
			return;
		}

		$('#report-row-pagination').show();
		$('#report-row-info').text(
			((ReportView.page - 1) * pageSize + 1) + ' - ' +
			Math.min(ReportView.page * pageSize, totalRows) + ' / ' + totalRows
		);
		$('#report-row-page-info').text(ReportView.page + ' / ' + totalPages);
		$('#report-row-pagination .previous').toggleClass('disabled', ReportView.page <= 1);
		$('#report-row-pagination .next').toggleClass('disabled', ReportView.page >= totalPages);
	},

	updateRowInfo: function () {
		ReportView.renderSectionPagination();
		ReportView.renderRowPagination();
	}
};

$(document).ready(function () {
	var sectionTables = $('.report-section');
	if (sectionTables.length > 1) {
		var currentIndex = 0;

		function renderPage(index) {
			sectionTables.hide();
			$(sectionTables.get(index)).show();
			$('#report-page-pagination .page-number').removeClass('current');
			$('#report-page-pagination .page-number[data-page-index="' + index + '"]').addClass('current');
			$('#report-page-pagination .previous').toggleClass('disabled', index === 0);
			$('#report-page-pagination .next').toggleClass('disabled', index === sectionTables.length - 1);
			$('#report-page-info').text((index + 1) + ' / ' + sectionTables.length);
		}

		$('#report-page-pagination').on('click', '.page-number', function (e) {
			e.preventDefault();
			var targetIndex = parseInt($(this).attr('data-page-index'), 10);
			if (!isNaN(targetIndex) && targetIndex >= 0 && targetIndex < sectionTables.length) {
				currentIndex = targetIndex;
				renderPage(currentIndex);
			}
		});

		$('#report-page-pagination').on('click', '.previous:not(.disabled)', function (e) {
			e.preventDefault();
			if (currentIndex > 0) {
				currentIndex -= 1;
				renderPage(currentIndex);
			}
		});

		$('#report-page-pagination').on('click', '.next:not(.disabled)', function (e) {
			e.preventDefault();
			if (currentIndex < sectionTables.length - 1) {
				currentIndex += 1;
				renderPage(currentIndex);
			}
		});

		renderPage(currentIndex);
	} else if ($('#myTable').length) {
		$('#myTable').DataTable();
		$('.dataTables_length').hide();
		$('#myTable_filter').hide();
	}

	ReportView.init();
});
