<?php
if(class_exists('SampleVariance') != TRUE) {
	
	class SampleVariance {
		
		private $mN 	= 0;
		private $mOldM 	= 0.0;
		private $mNewM 	= 0.0;
		private $mOldS 	= 0.0;
		private $mNewS 	= 0.0;
		
		public function __construct() {
		}
	
		public function clear() {
			$this->mN = 0;
		}
	
		public function push($x) {
			
			$this->mN++;
	
			// See Knuth - The Art of Computer Programming, Vol 2, 3rd edition, page 232
			if ($this->mN == 1)
			{
				$this->mOldM = $this->mNewM = $x;
				$this->mOldS = 0.0;
			}
			else
			{
				$this->mNewM = $this->mOldM + ($x - $this->mOldM)/$this->mN;
				$this->mNewS = $this->mOldS + ($x - $this->mOldM)*($x - $this->mNewM);
	
				// set up for next iteration
				$this->mOldM = $this->mNewM;
				$this->mOldS = $this->mNewS;
			}
		}
	
		public function number_data_values() {
			return $this->mN;
		}
	
		public function mean() {
			return ($this->mN > 0) ? $this->mNewM : 0.0;
		}
	
		public function variance() {
			return (($this->mN > 1) ? $this->mNewS/($this->mN - 1) : 0.0);
		}
	
		public function sample_deviation() {
			return sqrt($this->variance());
		}
	};
}
?>