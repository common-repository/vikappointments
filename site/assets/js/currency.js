(function($, w) {
	'use strict';

	/**
	 * Class used to deal with prices.
	 * 
	 * How to instantiate the singleton:
	 * VAPCurrency.getInstance('$', {
	 *   position: 2, // display currency before price
	 *   decimals: '.',
	 *   thousands: ',',
	 *   digits: 2,
	 * });
	 * 
	 * How to format prices:
	 * VAPCurrency.getInstance().format(15.32);
	 * // get rid of decimals on the fly
	 * VAPCurrency.getInstance().format(15.00, 0);
	 */
	w['VAPCurrency'] = class VAPCurrency {
			
		/**
		 * Singleton entry-point.
		 * 
		 * @see construct()
		 */
		static getInstance(symbol, options) {
			if (typeof VAPCurrency.instance === 'undefined' || !VAPCurrency.instance.symbol) {
				VAPCurrency.instance = new VAPCurrency(symbol, options);
			}

			return VAPCurrency.instance;
		}

		/**
		 * Class constructor
		 * 
		 * @param  string  symbol   The currency symbol (such as €, $, £ and so on).
		 * @param  array   options  An array of currency options:
		 *                          - position         int     The position of the currency (1 before, 2 after). In case the amount is negative
		 *                                                     the space between the currency and the amount won't be used.
		 *                          - decimals         string  The decimals separator character ("." or ",").
		 *                          - thousands        string  The thousands separator character ("," or ".").
		 *                          - digits           int     The number of decimal digits.
		 *                          - conversionRatio  float   The currency conversion ratio.
		 */
		constructor(symbol, options) {
			if (options === undefined) {
				options = {};
			}

			this.symbol    = symbol;
			this.position  = (options.hasOwnProperty('position')  ? options.position  : 1);
			this.decimals  = (options.hasOwnProperty('separator') ? options.separator : '.');
			this.thousands = (options.hasOwnProperty('thousands') ? options.thousands : ',');
			this.digits    = (options.hasOwnProperty('digits') ? parseInt(options.digits) : 2);

			this.conversionRatio = Math.abs((options.hasOwnProperty('conversionRatio') ? parseFloat(options.conversionRatio) : 1));
		}

		/**
		 * Formats the given price according to the configuration preferences.
		 * 
		 * @param   float   price  The price to format.
		 * @param   int     dig    Temporarily overrides the number of decimal digits.
		 * 
		 * @return  string  The formatted price.
		 */
		format(price, dig) {
			if (dig === undefined) {
				dig = this.digits;
			}

			price = parseFloat(price) / this.conversionRatio;

			// check whether the price is negative
			const isNegative = price < 0;

			// adjust to given decimals
			price = Math.abs(price).toFixed(dig);

			let _d = this.decimals;
			let _t = this.thousands;

			// make sure the decimal separator is a valid character
			if (!_d.match(/[.,\s]/)) {
				// revert to default one
				_d = '.';
			}

			// make sure the thousands separator is a valid character
			if (!_t.match(/[.,\s]/)) {
				// revert to default one
				_t = ',';
			}

			// make sure both the separators are not equals
			if (_d == _t) {
				_t = _d == ',' ? '.' : ',';
			}

			price = price.split('.');

			price[0] = price[0].replace(/./g, function(c, i, a) {
				return i > 0 && (a.length - i) % 3 === 0 ? _t + c : c;
			});

			if (isNegative) {
				// re-add negative sign
				price[0] = '-' + price[0];
			}

			if (price.length > 1) {
				price = price[0] + _d + price[1];
			} else {
				price = price[0];
			}

			if (Math.abs(this.position) == 1) {
				// do not use space in case the position is "-1"
				return price + (this.position == 1 ? ' ' : '') + this.symbol;
			}

			// do not use space in case the position is "-2"
			return this.symbol + (this.position == 2 ? ' ' : '') + price;
		}

		/**
		 * Safely sums 2 prices (a + b).
		 * 
		 * @param   float  a
		 * @param   float  b
		 * 
		 * @return  The resulting sum.
		 */
		sum(a, b) {
			// get rid of decimals for higher precision
			a *= Math.pow(10, this.digits);
			b *= Math.pow(10, this.digits);

			// do sum and go back to decimal
			return (Math.round(a) + Math.round(b)) / Math.pow(10, this.digits);
		}

		/**
		 * Safely subtracts 2 prices (a - b).
		 * 
		 * @param   float  a
		 * @param   float  b
		 * 
		 * @return  The resulting difference.
		 */
		diff(a, b) {
			// get rid of decimals for higher precision
			a *= Math.pow(10, this.digits);
			b *= Math.pow(10, this.digits);

			// do difference and go back to decimal
			return (Math.round(a) - Math.round(b)) / Math.pow(10, this.digits);
		}

		/**
		 * Safely multiplies 2 prices (a * b).
		 * 
		 * @param   float  a
		 * @param   float  b
		 * 
		 * @return  The resulting multiplication.
		 */
		multiply(a, b) {
			// get rid of decimals for higher precision
			a *= Math.pow(10, this.digits);
			b *= Math.pow(10, this.digits);

			// do multiplication and go back to decimal
			return (Math.round(a) * Math.round(b)) / Math.pow(10, this.digits * 2);
		}
	}

	// flag used to prevent the system from logging the same warning more than once
	let warningShown = false;

	/**
	 * Deprecated Currency instance.
	 */
	w['Currency'] = class Currency {
		static getInstance(symbol, options) {
			if (!warningShown) {
				console.warn('The Currency object is deprecated and will be removed soon. VAPCurrency should be used instead.');
				warningShown = true;				
			}
			
			return VAPCurrency.getInstance(symbol, options);
		}
	}
})(jQuery, window);