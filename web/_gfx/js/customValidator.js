/**
 * VALIDATOR - functions for jquery_form_valid
 */
function CustomValidator()
{	
	/**
	 * Check NIP
	 * 
	 * @param string nip nip to check
	 */
	this.checkNip = function(nip)
	{					 
		result = verifyNumber(2, nip);
				
		if (!result || result.lastIndexOf("Dobry") == -1)
		{			
			return false;
		}
		else
		{
			return true;
		}
	};
	
	/**
	 * Check iban
	 * 
	 * @param string iban iban to check
	 */
	this.checkIban = function(iban)
	{					 
		result = verifyNumber(7, iban);
				
		if (!result || result.lastIndexOf("Dobry") == -1)
		{			
			return false;
		}
		else
		{
			return true;
		}
	};
	
	/**
	 * Check price
	 *
	 * @param double price price to check
	 */
	this.checkPrice = function(price)
	{
		price = backendUtilities.str_replace(',', '.', price);
		
		if(!isNaN(price))
		{
			return true;
		}
		else
		{
			return false;
		}
	};
		
	/**
	 * Check date
	 *
	 * @param string date date to validate
	 */
	this.checkDate = function(date)
	{
		
		var re = /^\d{4}[-]\d{2}[-]\d{2}$/; 
		
		if(!date.match(re)) 
		{
			return false; 
		}
		
		return true;		
	};
	
	/**
	 * Check NIP
	 * 
	 * @param string nip nip to check
	 */
	this.checkNipValid = function(nip)
	{		
		nip = nip.val();
		result = verifyNumber(2, nip);
				
		if (!result || result.lastIndexOf("Dobry") == -1)
		{			
			return 'Niepoprawny';
		}
	};
	
	/**
	 * Check PESEL:
	 * 
	 * @param string pesel to check
	 */
	this.checkPeselValid = function(pesel)
	{		
		pesel = pesel.val();
		result = verifyNumber(0, pesel);
				
		if (!result || result.lastIndexOf("Dobry") == -1)
		{			
			return 'Niepoprawny';
		}
	};
	
	/**
	 * Check iban
	 * 
	 * @param string iban iban to check
	 */
	this.checkIbanValid = function(iban)
	{		
		iban = iban.val();
		result = verifyNumber(7, iban);
				
		if (!result || result.lastIndexOf("Dobry") == -1)
		{			
			return 'Niepoprawny';
		}
	};
	
	/**
	 * Check price
	 *
	 * @param double price price to check
	 */
	this.checkPriceValid = function(price)
	{
		price = price.val();
		price = backendUtilities.str_replace(',', '.', price);
		
		if(!isNaN(price))
		{
			return 'Niepoprawny';
		}
	};
		
	/**
	 * Check date
	 *
	 * @param string date date to validate
	 */
	this.checkDateValid = function(date)
	{
		date = date.val();
		var re = /^\d{4}[-]\d{2}[-]\d{2}$/; 
		
		if(!date.match(re)) 
		{
			return 'Niepoprawna';
		}		
	};
	
	/**
	 * Check IdentityCard
	 * 
	 * @param string card to check
	 */
	this.checkIdentityCardValid = function(number)
	{		
		number = number.val();
		result = verifyNumber(8, number);
				
		if (!result || result.lastIndexOf("Dobry") == -1)
		{			
			return 'Niepoprawny';
		}
	};

};
var customValidator = new CustomValidator();