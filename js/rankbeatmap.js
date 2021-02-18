function readableRankedStatus(ranked) {
	if (ranked == -1) {
		return "Not submitted";
	} else if (ranked == 0) {
		return "Pending"
	} else if (ranked == 1) {
		return "Need update"
	} else if (ranked == 2) {
		return "Ranked"
	} else if (ranked == 3) {
		return "Approved"
	} else if (ranked == 4) {
		return "Qualified"
	} else if (ranked == 5) {
		return "Loved"
	}
}

function readableYesNo(yn) {
	if (yn == 0) {
		return "No";
	} else {
		return "Yes";
	}
}

function printPP(pp, beatmapID) {
	if (pp == 0) {
		return `<a href="#" class="calc-pp" data-beatmapID="${beatmapID}">Ask Yohane</a>`;
	} else {
		return `${pp} pp`;
	}
}

$("document").ready(function() {
	var href = $(location).attr("href")
	var reload = href
	if (href.indexOf("force=1") === -1) {
		reload = href.substring(0, href.indexOf("#")) + "&force=1"
	}
	$.ajax("/letsapi/v1/cacheBeatmap", {
		method: "POST",
		data: {
			sid: bsid,
			refresh: force
		},
		success: function(data) {
			if (data.status == 200) {
				tableHtml = `<form id="rank-beatmap-form" action="submit.php" method="POST">
				<input name="csrf" type="hidden" value="${$("#csrf").val()}">
				<input name="action" value="rankBeatmapNew" hidden>`;
				tableHtml += `
					<table id="ranktable" class="table table-striped table-hover">
						<thead class="no-mobile">
							<th><i class="fa fa-music"></i>	Beatmap ID</th>
							<th>Difficulty Name</th>
							<th>Status</th>
							<th>Frozen</th>
							<th>PP (std SS)</th>
							<th>Rank</th>
							<th>Love</th>
							<th>Unrank (Pending)</th>
							<th>Reset status<br>from osu!api</th>
							<th>Don't edit</th>
							<th>Mark for Notes</th>
						</thead>
						<tbody>
				`;

				$.each(data.maps, function(index, value) {
					rowClass = "warning";
					if (value.status >= 2) {
						rowClass = "success";
					}
					tableHtml += `<tr class="text-center">
						<td class="${rowClass}">${escapeHtml(String(value.id))}</td>
						<td class="${rowClass}">${escapeHtml(String(value.diffName || value.name))}</td>
						<td class="${rowClass}"><b>${escapeHtml(readableRankedStatus(value.status))}</b></td>
						<td class="info"><span class="mobile-only rank">Frozen:</span> <span>${escapeHtml(String(readableYesNo(value.frozen)))}</span></td>
						<td class="info"><span class="mobile-only rank">PP:</span>${printPP(value.pp, value.id)}</td>
						<!-- ripple kalo kontol emang ya ini apaan anjir how to array woi what the fuck anjing -->
						<td class="success"><span class="mobile-only rank">Rank</span> <input name="beatmaps[${escapeHtml(String(value.id))}]${escapeHtml(String(value.id))}" value="rank" type="radio"></td>
						<td class="success"><span class="mobile-only">Love</span> <input name="beatmaps[${escapeHtml(String(value.id))}]${escapeHtml(String(value.id))}" value="love" type="radio"></td>
						<td class="success"><span class="mobile-only">Unrank</span> <input name="beatmaps[${escapeHtml(String(value.id))}]${escapeHtml(String(value.id))}" value="unrank" type="radio"></td>
						<td class="success"><span class="mobile-only">Reset status from osu!api</span> <input name="beatmaps[${escapeHtml(String(value.id))}]${escapeHtml(String(value.id))}" value="update" type="radio"></td>
						<td class="success"><span class="mobile-only">Don't edit</span> <input name="beatmaps[${escapeHtml(String(value.id))}]${escapeHtml(String(value.id))}" value="no" type="radio" checked></td>
						<td class="success"><span class="mobile-only">Set notes</span> <input name="beatmapNotes[${escapeHtml(String(value.id))}]" type="checkbox" value=1 /></td>
					</tr>`;
				});

				tableHtml += `</tbody></table>`;
				tableHtml += `<div class="mobile-flex">`
				tableHtml += `<button id="rank-all" type="button" class="btn btn-success"><span class="glyphicon glyphicon-thumbs-up"></span>	Rank everything</button>`;
				tableHtml += `	<button id="love-all" type="button" class="btn btn-pink"><span class="glyphicon glyphicon-heart"></span>	Love everything</button>`;
				tableHtml += `	<button id="unrank-all" type="button" class="btn btn-elegant"><span class="glyphicon glyphicon-thumbs-down"></span>	Unrank everything</button>`;
				tableHtml += `	<button id="update-all" type="button" class="btn btn-warning"><span class="glyphicon glyphicon-thumbs-down"></span>	Update everything</button>`;
				tableHtml += `<div style="margin-bottom: 5px;"></div>`;
				tableHtml += `<a href="http://osu.ppy.sh/s/${escapeHtml(String(bsid))}" target="_blank" type="button" class="btn btn-info"><span class="glyphicon glyphicon-arrow-down"></span>	Download beatmap set</a>`;
				tableHtml += `	<a href="${reload}" type="button" class="btn btn-danger"><span class="glyphicon glyphicon-refresh"></span>	Update set from osu!api</a>`;
				tableHtml += `</div>`
				tableHtml += `<hr>`;
				tableHtml += `<div class="alert alert-warning table-50-center"><i class="fa fa-exclamation-triangle"></i>	<b>Saving changes might take several seconds, especially if you want to update some beatmap from osu!api. Don't close the page until you see the success message to avoid errors.</b></div>`
				tableHtml += `<p style="text-align=center;">Extra beatmap notes:</p>`;
				tableHtml += `<textarea name="mapnotes"></textarea>`;
				tableHtml += `<button type="submit" class="btn btn-primary"><b><span class="glyphicon glyphicon-floppy-disk"></span>	Submit</b></button>`;
				tableHtml += `</form>`;
				$("#main-content").html(tableHtml);
			} else {
				$("#main-content").html(`
					<div class="alert alert-danger">
					<b>Error while getting beatmap data from osu!api.</b><br>
					Error code: ${escapeHtml(String(data.status))}<br>
					Message: ${escapeHtml(data.message)}
					</div>
				`);
			}

			updateTriggers();
		},
		error: function(data) {
			console.warn(data);
			$("#main-content").html(`
				<div class="alert alert-danger">
				Error in ajax request.
				</div>
			`);
		}
	});
});

function updateTriggers() {
	$(".calc-pp").click(function() {
		beatmapID = $(this).data("beatmapid");
		$(this).replaceWith(`<i class="fa fa-refresh fa-spin" data-beatmapid="${beatmapID}"></i>`);
		$.ajax("/letsapi/v1/pp", {
			method: "GET",
			data: {
				b: beatmapID
			},
			success: function(data) {
				if (data.status == 200) {
					$(`[data-beatmapid=${beatmapID}]`).replaceWith(`<span>${printPP(data.pp[0], beatmapID)}</span>`);
				} else {
					$(`[data-beatmapid=${beatmapID}]`).replaceWith(`<span>¯\\_(ツ)_/¯</span>`);
				}
				updateTriggers();
			}
		});
	});

	$("#rank-all").click(function() {
		$("[value=rank]").prop("checked", true);
	});

	$("#love-all").click(function() {
		$("[value=love]").prop("checked", true);
	});
	
	$("#unrank-all").click(function() {
		$("[value=unrank]").prop("checked", true);
	});

	$("#update-all").click(function() {
		$("[value=update]").prop("checked", true);
	});

	$("#rank-beatmap-form").submit(function() {
		$("#rank-beatmap-form").hide();
		$("#main-content").append(`
			<br><br>
			<div id="main-content">
				<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
				<h3>Saving new data...</h3>
				<h5>This might take a while</h5>
				<h5>Don't close this page</h5>
			</div>`);
	});
}
