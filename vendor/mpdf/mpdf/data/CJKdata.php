<?php
// mPDF 2.5

// BIG 5
$cw = array(
	32 => 250, 33 => 250, 34 => 408, 35 => 668, 36 => 490, 37 => 875, 38 => 698, 39 => 250, 40 => 240, 41 => 240,
	42 => 417, 43 => 667, 44 => 250, 45 => 313, 46 => 250, 47 => 520, 48 => 500, 49 => 500, 50 => 500, 51 => 500,
	52 => 500, 53 => 500, 54 => 500, 55 => 500, 56 => 500, 57 => 500, 58 => 250, 59 => 250, 60 => 667, 61 => 667,
	62 => 667, 63 => 396, 64 => 921, 65 => 677, 66 => 615, 67 => 719, 68 => 760, 69 => 625, 70 => 552, 71 => 771,
	72 => 802, 73 => 354, 74 => 354, 75 => 781, 76 => 604, 77 => 927, 78 => 750, 79 => 823, 80 => 563, 81 => 823,
	82 => 729, 83 => 542, 84 => 698, 85 => 771, 86 => 729, 87 => 948, 88 => 771, 89 => 677, 90 => 635, 91 => 344,
	92 => 520, 93 => 344, 94 => 469, 95 => 500, 96 => 250, 97 => 469, 98 => 521, 99 => 427, 100 => 521, 101 => 438,
	102 => 271, 103 => 469, 104 => 531, 105 => 250, 106 => 250, 107 => 458, 108 => 240, 109 => 802, 110 => 531, 111 => 500,
	112 => 521, 113 => 521, 114 => 365, 115 => 333, 116 => 292, 117 => 521, 118 => 458, 119 => 677, 120 => 479, 121 => 458,
	122 => 427, 123 => 480, 124 => 496, 125 => 480, 126 => 667,
	17601 => 500,
);
$this->Big5_widths=$cw;


// GB
$cw = array(
	32 => 207, 33 => 270, 34 => 342, 35 => 467, 36 => 462, 37 => 797, 38 => 710, 39 => 239, 40 => 374, 41 => 374,
	42 => 423, 43 => 605, 44 => 238, 45 => 375, 46 => 238, 47 => 334, 48 => 462, 49 => 462, 50 => 462, 51 => 462,
	52 => 462, 53 => 462, 54 => 462, 55 => 462, 56 => 462, 57 => 462, 58 => 238, 59 => 238, 60 => 605, 61 => 605,
	62 => 605, 63 => 344, 64 => 748, 65 => 684, 66 => 560, 67 => 695, 68 => 739, 69 => 563, 70 => 511, 71 => 729,
	72 => 793, 73 => 318, 74 => 312, 75 => 666, 76 => 526, 77 => 896, 78 => 758, 79 => 772, 80 => 544, 81 => 772,
	82 => 628, 83 => 465, 84 => 607, 85 => 753, 86 => 711, 87 => 972, 88 => 647, 89 => 620, 90 => 607, 91 => 374,
	92 => 333, 93 => 374, 94 => 606, 95 => 500, 96 => 239, 97 => 417, 98 => 503, 99 => 427, 100 => 529, 101 => 415,
	102 => 264, 103 => 444, 104 => 518, 105 => 241, 106 => 230, 107 => 495, 108 => 228, 109 => 793, 110 => 527, 111 => 524,
	112 => 524, 113 => 504, 114 => 338, 115 => 336, 116 => 277, 117 => 517, 118 => 450, 119 => 652, 120 => 466, 121 => 452,
	122 => 407, 123 => 370, 124 => 258, 125 => 370, 126 => 605,
);
$this->GB_widths=$cw;

// Japanese
$cw = array(
	32 => 278, 33 => 299, 34 => 353, 35 => 614, 36 => 614, 37 => 721, 38 => 735, 39 => 216, 40 => 323, 41 => 323,
	42 => 449, 43 => 529, 44 => 219, 45 => 306, 46 => 219, 47 => 453, 48 => 614, 49 => 614, 50 => 614, 51 => 614,
	52 => 614, 53 => 614, 54 => 614, 55 => 614, 56 => 614, 57 => 614, 58 => 219, 59 => 219, 60 => 529, 61 => 529,
	62 => 529, 63 => 486, 64 => 744, 65 => 646, 66 => 604, 67 => 617, 68 => 681, 69 => 567, 70 => 537, 71 => 647,
	72 => 738, 73 => 320, 74 => 433, 75 => 637, 76 => 566, 77 => 904, 78 => 710, 79 => 716, 80 => 605, 81 => 716,
	82 => 623, 83 => 517, 84 => 601, 85 => 690, 86 => 668, 87 => 990, 88 => 681, 89 => 634, 90 => 578, 91 => 316,
	92 => 614, 93 => 316, 94 => 529, 95 => 500, 96 => 387, 97 => 509, 98 => 566, 99 => 478, 100 => 565, 101 => 503,
	102 => 337, 103 => 549, 104 => 580, 105 => 275, 106 => 266, 107 => 544, 108 => 276, 109 => 854, 110 => 579, 111 => 550,
	112 => 578, 113 => 566, 114 => 410, 115 => 444, 116 => 340, 117 => 575, 118 => 512, 119 => 760, 120 => 503, 121 => 529,
	122 => 453, 123 => 326, 124 => 380, 125 => 326, 126 => 387, 127 => 216, 128 => 453, 129 => 216, 130 => 380, 131 => 529,
	132 => 299, 133 => 614, 134 => 614, 135 => 265, 136 => 614, 137 => 475, 138 => 614, 139 => 353, 140 => 451, 141 => 291,
	142 => 291, 143 => 588, 144 => 589, 145 => 500, 146 => 476, 147 => 476, 148 => 219, 149 => 494, 150 => 452, 151 => 216,
	152 => 353, 153 => 353, 154 => 451, 156 => 1075, 157 => 486, 158 => 387, 159 => 387, 160 => 387, 161 => 387,
	162 => 387,	163 => 387, 164 => 387, 165 => 387, 166 => 387, 167 => 387, 168 => 387, 170 => 880, 171 => 448,
	172 => 566, 173 => 716,	174 => 903, 175 => 460, 176 => 805, 177 => 275, 178 => 276, 179 => 550, 180 => 886, 181 => 582,
	182 => 529, 183 => 738,	184 => 529, 185 => 738, 186 => 357, 187 => 529, 188 => 406, 189 => 406, 190 => 575, 191 => 406,
	192 => 934, 193 => 934,	194 => 934, 195 => 646, 196 => 646, 197 => 646, 198 => 646, 199 => 646, 200 => 646, 201 => 617,
	202 => 567, 203 => 567, 204 => 567, 205 => 567, 206 => 320, 207 => 320, 208 => 320, 209 => 320, 210 => 681, 211 => 710,
	212 => 716, 213 => 716, 214 => 716, 215 => 716, 216 => 716, 217 => 529, 218 => 690, 219 => 690, 220 => 690, 221 => 690,
	222 => 634, 223 => 605, 224 => 509, 225 => 509, 226 => 509, 227 => 509, 228 => 509, 229 => 509, 230 => 478, 231 => 503,
	232 => 503, 233 => 503, 234 => 503, 235 => 275, 236 => 275, 237 => 275, 238 => 275, 239 => 550, 240 => 579, 241 => 550,
	242 => 550, 243 => 550, 244 => 550, 245 => 550, 246 => 529, 247 => 575, 248 => 575, 249 => 575, 250 => 575, 251 => 529,
	252 => 578, 253 => 529, 254 => 517, 255 => 634, 256 => 578, 257 => 445, 258 => 444, 259 => 842, 260 => 453, 261 => 614,
);


$_cr = array(
	array(231, 632, 500), // half-width
	array(8718, 8718, 500),
	array(9738, 9757, 250), // quarter-width
	array(9758, 9778, 333), // third-width
	array(12063, 12087, 500),
);
foreach($_cr as $_r) {
	for($i = $_r[0]; $i <= $_r[1]; $i++) {
		$cw[$i+31] = $_r[2];
	}
}
$this->SJIS_widths=$cw;

// Korean
$cw = array(
	32 => 333, 33 => 416, 34 => 416, 35 => 833, 36 => 625, 37 => 916, 38 => 833, 39 => 250, 40 => 500, 41 => 500,
	42 => 500, 43 => 833, 44 => 291, 45 => 450, 46 => 291, 47 => 375, 48 => 625, 49 => 625, 50 => 625, 51 => 625,
	52 => 625, 53 => 625, 54 => 625, 55 => 625, 56 => 625, 57 => 625, 58 => 333, 59 => 333, 60 => 833, 61 => 833,
	62 => 916, 63 => 500, 64 => 1000, 65 => 791, 66 => 708, 67 => 708, 68 => 750, 69 => 708, 70 => 666, 71 => 750,
	72 => 791, 73 => 375, 74 => 500, 75 => 791, 76 => 666, 77 => 916, 78 => 791, 79 => 750, 80 => 666, 81 => 750,
	82 => 708, 83 => 666, 84 => 791, 85 => 791, 86 => 750, 87 => 1000, 88 => 708, 89 => 708, 90 => 666, 91 => 500,
	92 => 375, 93 => 500, 94 => 500, 95 => 500, 96 => 333, 97 => 541, 98 => 583, 99 => 541, 100 => 583, 101 => 583,
	102 => 375, 103 => 583, 104 => 583, 105 => 291, 106 => 333, 107 => 583, 108 => 291, 109 => 875, 110 => 583, 111 => 583,
	112 => 583, 113 => 583, 114 => 458, 115 => 541, 116 => 375, 117 => 583, 118 => 583, 119 => 833, 120 => 625, 121 => 625,
	122 => 500, 123 => 583, 124 => 583, 125 => 583, 126 => 750,
);
$_cr = array(
	array(8094, 8190, 500)
);
foreach($_cr as $_r) {
	for($i = $_r[0]; $i <= $_r[1]; $i++) {
		$cw[$i+31] = $_r[2];
	}
}
$this->UHC_widths=$cw;
