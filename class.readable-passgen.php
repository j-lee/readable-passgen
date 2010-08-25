<?php
/***************************************************************************************************
 *
 * Readable Passgen v1.0 (August 24, 2010)
 * class.readable-passgen.php
 * http://github.com/j-lee/readable-passgen
 *
 * Copyright 2010 James Lee
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 **************************************************************************************************/

class PasswordGenerator {

	// lists
	public $list = array();			// list of generated passwords
	public $exclude = array();		// list of passwords to exclude from generation
	public $dirty = array();		// list of dirty words generated

	// configuration
	public $total = 1000;			// total number of passwords to generate
	public $capitalize = 0;			// capitalize alphabetical characters
	public $minlength = 6;			// minimum string length
	public $maxlength = 8;			// maximum string length
	public $maxtries = 10;			// tries before using numbers in passworsd
	public $shuffle = 0;			// shuffle final results
	public $print = TRUE;			// print results back to browser during generation

	// statistics
	public $collisions = 0;			// number of collisions
	public $firstCollision = 0;	// occurence of first collision
	public $withnum = 0;			// number of passwords with numbers
	public $firstNum = 0;			// occurence of first password with number
	public $filtered = 0;			// number of times dirty words filtered

	// Constructor
	public function PasswordGenerator($timeout=1800, $zlib=0) {
		set_time_limit($timeout);  // set a timeout limit
		ini_set('zlib.output_compression', $zlib);  // having compression on can mess up output
	}

	// generatePasswords()
	// Main function that generates the batch passwords
	// Parameters: none
	// Output: (array) list of passwords
	public function generatePasswords() {
		$count = 1;

		while ($count <= $this->total) {
			$password = '';
			$l = 0;
			$unique = FALSE;
			$tries = 0;
			$count_collision = TRUE;
			while ($l < $this->minlength || $l > $this->maxlength || !$unique) {  // while password is not unique and not within length requirements
				$hasnum = FALSE;
				$password = $this->makeReadablePassword(1, FALSE);
				$unique = !in_array($password, $this->list) && !in_array($password, $this->exclude) && $this->isClean($password);
				if (!$unique && $this->isClean($password) && $tries >= $this->maxtries) {  // if tried too many times for a word, put a number
					while (!$unique) {
						$ran = rand(1, 99);
						$unique = !in_array($password.$ran, $this->list) && !in_array($password.$ran, $this->exclude);
						if ($unique) {  // if number is unique with the number, save and re-test
							$password .= $ran;
							$hasnum = TRUE;
						}
					}
				}
				if (!$unique && $count_collision) {  // count unique collisions (once per password entry)
					if ($this->firstCollision == 0) $this->firstCollision = $count;
					$this->collisions++;
					$count_collision = FALSE;
				}
				$l = strlen($password);
				$tries++;
			}
			if ($hasnum) {  // count entries with numbers
				if ($this->firstNum == 0) $this->firstNum = $count;
				$this->withnum++;
			}
			$this->list[] = $password;
			$showPassword = $this->capitalize ? strtoupper($password) : $password;
			if ($this->print) echo "$count $showPassword <span class=\"lite\">($tries attempts)</span><br />";
			$count++;
		}

		if ($this->capitalize) {  // if capitalize
			foreach ($this->list as $key => $password)
				$this->list[$key] = strtoupper($password);
		}

		if ($this->shuffle) {
			shuffle($this->list);
			if ($this->print) echo '&gt; All results has been shuffled<br />';
		}

		return $this->list;
	}

	// makeReadablePassword()
	// Generates a readable password
	// Thanks to: http://www.anyexample.com/programming/php/php__password_generation.xml
	// Parameters: (int) number of syllables in the password
	//			(boolean) use a prefix or not
	// Output: 	(array) list of passwords
	protected function makeReadablePassword($syllables=3, $usePrefix=FALSE) {
		if (!function_exists('rand_e')) {
			// returns random array element
			function rand_e(&$arr) {
				return $arr[rand(0, sizeof($arr)-1)];
			}
		}

		$doubles = array('n', 'm', 't', 's', 'l', 'd', 'p', 'r');
		$vowels = array('a', 'o', 'e', 'i', 'y', 'u', 'ou', 'oo');
		$consonants = array('w', 'r', 't', 'p', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'qu');
		$prefix = array('aero', 'anti', 'auto', 'bi', 'bio', 'cine', 'deca', 'demo', 'dyna', 'eco', 'ergo', 'geo',
						'gyno', 'hypo', 'kilo', 'mega', 'tera', 'mini', 'nano', 'duo');
		$suffix = array('able', 'ible', 'age', 'acy', 'isy', 'al', 'eal', 'ial', 'ance', 'ence', 'ant', 'er', 'ed',
						'ery', 'dom', 'ent', 'en', 'eur', 'er', 'or', 'est', 'ful', 'full', 'hood', 'ile', 'il', 'ier',
						'ior', 'ify', 'ic', 'ing', 'ion', 'ism', 'ish', 'ist', 'ity', 'ty', 'itis', 'ive', 'ize', 'less',
						'let', 'ly', 'ment', 'meter', 'ness', 'ology', 'ous', 'ious', 'ship', 'scope', 'some', 'tion', 'ace',
						'sion', 'ty', 'ward', 'y', 'cy', 'ce', 'ere', 'uus', 'ium', 'ax', 'id', 'ary', 'ine', 'ise', 'ese',
						'ant', 'our', 'ack', 'ogy', 'asm', 'ath', 'ith', 'ley', 'any', 'int', 's', 'ate', 'ner', 'ole');

		$password = $usePrefix ? rand_e($prefix) : '';
		$passwordSuffix = rand_e($suffix);

		for ($i=0; $i<$syllables; $i++) {
			// selecting random consonant
			$c = rand_e($consonants);
			if (in_array($c, $doubles) && ($i != 0)) {  // maybe double it
				if (rand(0, 2) == 1)  // 33% probability
					$c .= $c;
			}
			$password .= $c;

			// selecting random vowel
			$password .= rand_e($vowels);

			if ($i == $syllables - 1)  // if suffix begin with vovel
				if (in_array($passwordSuffix[0], $vowels))  // add one more consonant
					$password .= rand_e($consonants);
		}

		// selecting random suffix
		$password .= $passwordSuffix;

		return $password;
	}

	// isClean()
	// Tests whether a word is clean or not
	// Parameters: (string) a word to test
	// Output: 	(boolean) 'TRUE' if the word is clean, 'FALSE' if it contains a bad word
	protected function isClean($word) {
		$badlist = array('penis', 'clit', 'cunt', 'cock', 'shit', 'fuck', 'dick', 'boob', 'damn', 'jack', 'dying', 'testicle',
							'anus', 'hooker', 'pimp', 'porn', 'suck', 'lick', 'sex', 'blow', 'cum', 'racist', 'latex', 'flog',
							'nigg', 'kill', 'raping', 'rape', 'murder', 'vagina', 'bitch', 'slut', 'jerk', 'hate', 'zex', 'fuk',
							'fetish', 'fag', 'semen', 'molest', 'death', 'dead', 'pussy', 'fart', 'orgy', 'orgasm', 'hole',
							'nipple', 'wang');
		while (list(, $badword) = each($badlist)) {
			if (stripos($word, $badword) !== FALSE) {
				$this->filtered++;
				$this->dirty[] = $word;
				return FALSE;
			}
		}
		return TRUE;
	}

}