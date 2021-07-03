
$(document).ready(function() {
	if (typeof UserID == "undefined" || typeof Mode == "undefined") {
		return;
	}
	getScores("best");
	getScores("recent");
});

var currentPage = {
	best: 1,
	recent: 1,
};
var bestIndex = 0;

function getScores(type) {
	var btn = $(".load-more-user-scores[data-rel='" + type + "']");
	btn.attr("disabled", "true");
	$.getJSON("/api/v1/users/scores/" + type, {
		id: UserID,
		l: 20,
		p: currentPage[type],
		mode: Mode,
	}, function(data) {
		if (data.code != 200) {
			alert("Whoops! We had an error while trying to show scores for this user :( Please report this!");
		}
		var tb = $("#" + type + "-plays-table");
		$.each(data.scores, function(k, v) {
			var sw = (type == "recent" ? "success" : "warning");
			var u = "<tr>";
			u +=  '<td class="' + sw + '">\
						<p class="text-left">\
							<img src="images/ranks/' + getRank(Mode, v.mods, v.accuracy, v.count_300, v.count_100, v.count_50, v.count_miss) + '.png"></img> \
							'+ (v.beatmap === null ? "Unknown beatmap" :  v.beatmap.song_name) +'\
							<b>' + getScoreMods(v.mods) + '</b> (' + v.accuracy.toFixed(2) + '%) <br>\
							<small>' + timeSince(new Date(v.time)) + ' ago</small>\
						</p></td>';
			u += '<td class="' + sw + '"><p class="text-right"><b>';
			var small = "";
			if (Mode == 0 || Mode == 3) {
				u += "<span title='Score: " + addCommas(v.score) + "'>" + addCommas(v.pp.toFixed(2)) + "pp</span>";
				if (type == "best") {
					var perc = Math.pow(0.95, bestIndex);
					var wpp  = v.pp * perc;
					small = "<small>weighted " + Math.round(perc * 100) + "% (" + addCommas(Math.round(wpp)) + " pp)</small>";
					bestIndex++;
				}
			} else {
				u += addCommas(v.score);
			}
			if (v.completed == 3) {
				u += ' <a href="/web/replays/' + v.id + '"><i class="fa fa-star"></i></a>';
			}
			u += '</b><br>' + small + '</p></td>';
			u += "</tr>";
			tb.append(u);
		});
		if (data.scores.length == 20)
			btn.removeAttr("disabled");
		currentPage[type]++;
	});
}

$(".load-more-user-scores").click(function() {
	if ($(this).attr("disabled"))
		return;
	getScores($(this).data("rel"));
});

function timeSince(date) {

    var seconds = Math.floor((new Date() - date) / 1000);

    var interval = Math.floor(seconds / 31536000);

    if (interval > 1) {
        return interval + " years";
    }
    interval = Math.floor(seconds / 2592000);
    if (interval > 1) {
        return interval + " months";
    }
    interval = Math.floor(seconds / 86400);
    if (interval > 1) {
        return interval + " days";
    }
    interval = Math.floor(seconds / 3600);
    if (interval > 1) {
        return interval + " hours";
    }
    interval = Math.floor(seconds / 60);
    if (interval > 1) {
        return interval + " minutes";
    }
    return Math.floor(seconds) + " seconds";
}

function getScoreMods(m,f) {
	var r = [];
	if (m & NoFail) r.push('NF');
	if (m & Easy) r.push(f ? 'EM' : 'EZ');
	if (m & NoVideo) r.push('NV');
	if (m & Hidden) r.push('HD');
	if (m & HardRock) r.push('HR');
	if (m & Perfect) r.push('PF');
	else if (m & SuddenDeath) r.push('SD');
	if (m & Nightcore) r.push('NC');
	else if (m & DoubleTime) r.push('DT');
	if (m & Relax) r.push(f ? 'RL' : 'RX');
	else if (m & Relax2) r.push('ATP');
	if (m & HalfTime) r.push('HT');
	if (m & Flashlight) r.push('FL');
	if (m & Autoplay) r.push('Auto');
	if (m & SpunOut) r.push('SO');
	let nK = 0;
	if (m & (Key1 | Key2 | Key3 | Key4 | Key5 | Key6 | Key7 | Key8 | Key9 | Key10)) {
		if (m & Key1) nK = 1;
		else if (m & Key2) nK = 2;
		else if (m & Key3) nK = 3;
		else if (m & Key4) nK = 4;
		else if (m & Key5) nK = 5;
		else if (m & Key6) nK = 6;
		else if (m & Key7) nK = 7;
		else if (m & Key8) nK = 8;
		else if (m & Key9) nK = 9;
		if (m & Key10) nK *= 2;
	}
	if (nK) r.push(`${nK}K`);
	else if (m & KeyCoop) r.push('DP');
	if (m & FadeIn) r.push('FI');
	if (m & Random) r.push('RAN');
	if (m & LastMod) r.push('CIN');
	if (r.length > 0) {
		return "+ " + r.join(', ');
	} else {
		return '';
	}
}

var None = 0;
var NoFail = 1;
var Easy = 2;
var NoVideo = 4;
var Hidden = 8;
var HardRock = 16;
var SuddenDeath = 32;
var DoubleTime = 64;
var Relax = 128;
var HalfTime = 256;
var Nightcore = 512;
var Flashlight = 1024;
var Autoplay = 2048;
var SpunOut = 4096;
var Relax2 = 8192;
var Perfect = 16384;
var Key4 = 32768;
var Key5 = 65536;
var Key6 = 131072;
var Key7 = 262144;
var Key8 = 524288;
var keyMod = 1015808;
var FadeIn = 1048576;
var Random = 2097152;
var LastMod = 4194304;
var Key9 = 16777216;
var Key10 = 33554432;
var Key1 = 67108864;
var Key3 = 134217728;
var Key2 = 268435456;

function addCommas(nStr) {
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

function getRank(gameMode, mods, acc, c300, c100, c50, cmiss) {
	var total = c300+c100+c50+cmiss;

	var hdfl = (mods & (Hidden | Flashlight | FadeIn)) > 0;

	var ss = hdfl ? "sshd" : "ss";
	var s = hdfl ? "shd" : "s";

	switch(gameMode) {
		case 0:
		case 1:
			var ratio300 = c300 / total;
			var ratio50 = c50 / total;

			if (ratio300 == 1)
				return ss;

			if (ratio300 > 0.9 && ratio50 <= 0.01 && cmiss == 0)
				return s;

			if ((ratio300 > 0.8 && cmiss == 0) || (ratio300 > 0.9))
				return "a";

			if ((ratio300 > 0.7 && cmiss == 0) || (ratio300 > 0.8))
				return "b";

			if (ratio300 > 0.6)
				return "c";

			return "d";

		case 2:
			if (acc == 100)
				return ss;

			if (acc > 98)
				return s;

			if (acc > 94)
				return "a";

			if (acc > 90)
				return "b";

			if (acc > 85)
				return "c";

			return "d";

		case 3:
			if (acc == 100)
				return ss;

			if (acc > 95)
				return s;

			if (acc > 90)
				return "a";

			if (acc > 80)
				return "b";

			if (acc > 70)
				return "c";

			return "d";
	}
}
