<?php
	namespace Traits;

	trait Parse {
		/**
		 * Sammenligner to arrays, for at udføre en af to opgaver
		 * 1. Parse af valgfrie variabler, overskriver standard med hvad der er givet
		 * 2. Tjek om de rigtige variabler er givet
		 *
		 * @param   Array    Input, som skal overskrives eller tjekkes om indholder det rigtige
		 * @param   Array    Standardværdierne eller de værdier der kræves at der er i input
		 * @param   Boolean  Om der skal køres i version 2, standard er varsion 1
		 * 
		 * @return  Array    Overskrevede standard værdier eller input, hvis der sammenlignes tvungne felter
		 */
		private function parse(Array $input, Array $defaults, $required = false) : Array {
			if($required) {
				foreach($defaults as $k) {
					if(!\array_key_exists($k, $input))
						throw new BasicTraitException($this->errors[1] . "$k", 1);
				}
			} else {
				foreach($defaults as $k => $opt) {
					if(!\array_key_exists($k, $input))
						$input[$k] = $opt;
				}
			}
			return $input;
		}
	}
?>