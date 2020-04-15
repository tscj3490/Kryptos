function suma_cyfr(ii) {

	// alert( (ii % 10) + Math.floor(ii/10) );

	return (ii % 10) + Math.floor(ii / 10);

}

function CodeOf(znak) {

	var A = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

	return A.indexOf(znak);

} // fun codeOf()

function verifyNumber() { /* copyright R.J.Żyłła 2000-2006 */

	// alert( verifyNumber.arguments[0] );

	var rodzajNum = verifyNumber.arguments[0];

	var tempStr = verifyNumber.arguments[1];

	if (rodzajNum == 14)

	{
		return verifyEURO(tempStr);
	}

	if (rodzajNum != 7)

		if (rodzajNum != -1)

		{
			return verifyDATA(rodzajNum, tempStr);
		}

		else {
			return 'Niewybrany rodzaj numeru. Brak danych'
		}

	if (rodzajNum == 7)

	{
		return verifyIBAN(tempStr);
	}

	return false;

}// fun verifyNumber

function verifyDate(dd, mm, yyyy) {

	// amerykański format zapisu mm/dd/yyyy

	var data_am = mm + '/' + dd + '/' + yyyy;

	var date_obj = new Date(data_am);

	with (date_obj)

		return (getDate() == dd && getMonth() == (mm - 1) && getFullYear() == yyyy);

}// fun verifyDate

function DatawPeselu(tempStr) {

	var century = new Array(1900, 2000, 2100, 2200, 1800);

	data = tempStr.substring(0, 6);

	dd = parseInt(data.substring(4, 6), 10); // od do

	cc = parseInt(data.substring(2, 4), 10);

	bb = parseInt(data.substring(0, 2), 10);

	mm = cc % 20;

	rr = century[Math.floor(cc / 20)] + bb;

	return (verifyDate(dd, mm, rr) ? '' : '   błędna data w PESELu: ' + dd
			+ '.' + mm + '.' + rr)

}

function trzyLitery(tempStr) {

	var litery = true;

	for (i = 0; i < 2; i++) {

		znak = tempStr.charAt(i); // alert( znak );

		litery = litery && ((znak >= 'A') && (znak <= 'Z'));

	}

	return litery; // 3 litery na poczatku

} // fun trzyLitery

function verifyDATA() { /* copyright R.J.Żyłła 2000-2007 */

	// alert( verifyDATA.arguments[0] );

	var rodzajNum = verifyDATA.arguments[0];

	var tempStr = verifyDATA.arguments[1];

	var nazwy = new Array('PESEL', 'REGON', 'NIP', 'BANK', 'ISBN10', 'CCARD',

	'IACS', 'IBAN', 'DowOsob', 'NrLekarza', 'IMEI', 'NrGospIACS',

	'EAN8', 'EAN13', 'EURO');

	// alert( nazwy[ rodzajNum ] );

	var dlug = new Array(11, 9, 10, 8, 10, 16, 12, 28, 9, 7, 15, 9, 8, 13, 12);

	var mody = new Array(10, 11, 11, 10, 11, 10, 10, 97, 10, 11, 10, 7, 10, 10,
			9);

	var wagi = new Array(

	new Array(1, 3, 7, 9, 1, 3, 7, 9, 1, 3, 0, 0, 0, 0, 0, 0), /* PESEL */

	new Array(8, 9, 2, 3, 4, 5, 6, 7, 0, 0, 0, 0, 0, 0, 0, 0), /* REGON */

	new Array(6, 5, 7, 2, 3, 4, 5, 6, 7, 0, 0, 0, 0, 0, 0, 0), /* NIP */

	new Array(7, 1, 3, 9, 7, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0), /* BANK */

	new Array(10, 9, 8, 7, 6, 5, 4, 3, 2, 0, 0, 0, 0, 0, 0, 0), /* ISBN10 */

	new Array(2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1), /* CCARD */

	new Array(3, 1, 3, 1, 3, 1, 3, 1, 3, 1, 3, 0, 0, 0, 0, 0), /* IACS */

	new Array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), /* blank */

	new Array(7, 3, 1, 0, 7, 3, 1, 7, 3, 1, 0, 0, 0, 0, 0, 0), /* DowOso */

	new Array(0, 1, 2, 3, 4, 5, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0), /* NrPrWZ */

	new Array(1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2), /* IMEI */

	new Array(1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2), /*
																 * sztuczny
																 * NrGosp
																 */

	new Array(3, 1, 3, 1, 3, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0), /* EAN8 */

	new Array(1, 3, 1, 3, 1, 3, 1, 3, 1, 3, 1, 3, 1, 3, 0, 0));/* EAN13 */

	/* NIP 6 5 7 2 3 4 5 6 7 MOD 11 plus ostatnia cyfra */

	/* REGON 8 9 2 3 4 5 6 7 MOD 11 plus ostatnia cyfra */

	/* BANKI 7 1 3 9 7 1 3 MOD 10 plus ostatnia cyfra */

	/* ISBN 10 9 8 7 6 5 4 3 2 MOD 11 */

	/* NPWZ 0 1 2 3 4 5 6 MOD 11 ck na poz. 1 */

	var cyfry = new Array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
			0, 0);

	var Litera = new Array('A', 'A', 'A', 'A', 'A', 'A', 'A', 'A');

	if (tempStr != '') { // zamiana znaków na liczby

		comment = ' '; // alert( tempStr );

		if (rodzajNum == 8) {

			tempStr = compactNonAlfa(tempStr);

			if (!trzyLitery(tempStr))
				comment = ' formalnie, ale brak trzech liter'
		}

		else
			tempStr = compactString(tempStr);

		// alert( tempStr )

		if (rodzajNum == 5) { // wydluz spacjami

			tempStr = "0000000000" + tempStr;
			L = tempStr.length;

			tempStr = tempStr.substring(L - 16, L); // L = tempStr.length;

			// alert('L='+L + ' ['+tempStr+']' );

		}

		L = tempStr.length;

		for (i = 0; i < L; i++) {

			znak = tempStr.charAt(i);

			// alert( znak );

			if (rodzajNum == 8) {

				Litera[i] = znak;

				if ((znak >= 'A') && (znak <= 'Z')) {

					znak = CodeOf(znak) % 10;

					// tempStr = tempStr.substring(0,i) + ca
					// +tempStr.substring(i+1,tempStr.length);

				} // if

			}

			if (znak == 'X')
				cyfry[i] = 10

			else
				cyfry[i] = parseInt(znak);

		} // for

		// alert( 'L= '+L+' dlug[tabl]='+dlug[rodzajNum] );

		// alert('nr rodz numeru= '+ rodzajNum + ' cyfry=' + cyfry[0] + cyfry[1]
		// + cyfry[2] + cyfry[3] + cyfry[4] + cyfry[5] + cyfry[6] + cyfry[7] +
		// cyfry[8] );

		if ((L == dlug[rodzajNum]) || (rodzajNum == 5)) {

			suma = 0;

			if (rodzajNum == 5) // CCard

				for (i = 0; i < L - 1; i++) {

					suma = suma + suma_cyfr(wagi[rodzajNum][i] * cyfry[i]);

					// alert( suma );

				}

			else
				for (i = 0; i < L - 1; i++) {

					suma = suma + (wagi[rodzajNum][i] * cyfry[i]);

					// alert( wagi[rodzajNum][i]+'*'+cyfry[i]+' suma='+suma );

				}

			if (rodzajNum == 8 || rodzajNum == 9) // dodaj iloczyn ostatniej

				suma = suma + (wagi[rodzajNum][L - 1] * cyfry[L - 1]);

			// alert( wagi[rodzajNum][i]+'*'+cyfry[i]+' suma='+suma );

			if (rodzajNum == 10) { // IMEI

				suma = 0;

				for (i = 0; i < L - 1; i++) {

					suma = suma + suma_cyfr(wagi[rodzajNum][i] * cyfry[i]);

					// alert( 'Suma IMEI = ' + suma );

				}

			}

			if (rodzajNum == 11) { /* NrGosp IACS */

				// alert('wejscie w IACS');

				sp = 0;
				snp = 0;
				lp = 0;

				for (i = 0; i < L - 1; i++)

					if (cyfry[i] % 2 == 0) {

						sp += cyfry[i];

						lp += 1;

					}

					else
						snp += cyfry[i];

				suma = (23 * sp + 17 * snp + lp);

				// alert( 'sp=' + sp + ' snp=' + snp +' lp=' + lp + ' Suma
				// NrGosp= ' + suma );

			}

			suma = suma % mody[rodzajNum];

			if (rodzajNum == 0)
				suma = (10 - suma) % 10; // PESEL

			if (rodzajNum == 1)
				suma = suma % 10; // REGON

			if (rodzajNum == 2)
				suma = suma; // NIP

			if (rodzajNum == 4)
				suma = (11 - suma) % 11; // ISBN10

			if (rodzajNum == 5)
				suma = (10 - suma) % 10; // CCARD

			if (rodzajNum == 6)
				suma = (10 - suma) % 10; // IACS

			if (rodzajNum == 10)
				suma = (10 - suma) % 10; // IMEI

			if (rodzajNum == 11)
				suma = suma % 7; // NrGospIACS

			if (rodzajNum == 12)
				suma = (10 - suma) % 10; // EAN8

			if (rodzajNum == 13)
				suma = (10 - suma) % 10; // EAN13

			wynikB = (suma == cyfry[L - 1]);

			if (rodzajNum == 8)

				wynikB = (suma % 10 == cyfry[3]);

			if (rodzajNum == 9) {

				wynikB = (suma % 11 == cyfry[0]);

				// alert('obl.cyfr='+suma+' cyfra='+cyfry[0]);

			}

			if (rodzajNum == 0)
				comment = DatawPeselu(tempStr);

			if (rodzajNum == 1) { // REGON

				num = cyfry[0] * 10 + cyfry[1];

				if ((num != 0) && (num % 2 == 0) && (num > 34))
					comment = 'błędny kod województwa.';

				if ((num != 0) && (num % 2 == 1) && (num > 97))
					comment = 'błędny kod województwa.';

			}

			if (rodzajNum == 2) // NIP

				if (cyfry[0] == 0 || cyfry[2] == 0)
					comment = 'błędny kod Urzędu';

			if (rodzajNum == 8) // Dowód

				if (Litera[0] > 'A' || Litera[1] == 'O' || Litera[1] == 'Q'
						|| Litera[2] == 'O' || Litera[2] == 'Q')

					comment = 'błędna litera serii';

			return (wynikB ? ' = Dobry' : ' = Błędny ');

		}

		else {

			wynikB = false;

			if ((L == dlug[rodzajNum] - 1) && (rodzajNum == 10)) {

				alert('dla numeru IMEI obliczyć cyfrę kontrolną ?');

				suma = 0;

				for (i = 0; i < L; i++) {

					suma = suma + suma_cyfr(wagi[rodzajNum][i] * cyfry[i]);

					// alert( 'Suma IMEI = ' + suma );

				} // alert( 'Suma IMEI = ' + suma );

				CK = (10 - suma % 10) % 10;

			}

		}

	}

	else

	{
		return ' Brak danych';

	}

	return false;

} // fun verifyDATA

function compactNonAlfa() {

	var inpstr = compactNonAlfa.arguments[0]; // document.forms[0].dane.value;

	var inpstl = inpstr.length; // document.forms[0].dane.length;

	// alert('compactNonAlfa = '+inpstr);

	for (i = 0; i < inpstr.length; i++) {

		znak = inpstr.charAt(i);

		znak = znak.toUpperCase(); // malym znakom pozwalamy sie zmienic na
									// wielkie

		// alert('inpStr='+inpstr.substring(0,i) +'?'+
		// inpstr.substring(i+1,inpstr.length));

		if (!((znak >= '0' && znak <= '9') || (znak >= 'A' && znak <= 'Z'))) {

			inpstr = inpstr.substring(0, i)
					+ inpstr.substring(i + 1, inpstr.length);

			i = i - 1;

		}

	}

	// alert('inpstr=' + inpstr.toUpperCase() );

	return inpstr.toUpperCase();

} // fun compactNonAlfa

function verifyIBAN() { /* copyright R.J.Żyłła 2003-2023 */

	var tempStr = verifyIBAN.arguments[0];

	// alert( verifyIBAN.arguments[0] );

	var kod = new Array(

	"AD", "AT", "BA", "BE", "BG", "CH", "CY", "CZ", "DE", "DK",

	"EE", "ES", "FI", "FO", "FR", "GB", "GI", "GL", "GR", "HR",

	"HU", "IE", "IL", "IS", "IT", "LI", "LT", "LU", "LV", "MC",

	"ME", "MK", "MT", "MU", "NL", "NO", "PL", "PT", "RO", "RS",

	"SE", "SI", "SK", "SM", "TN", "TR");

	// alert('kod.size' + kod.length );

	var dlu = new Array(

	24, 20, 20, 16, 22, 21, 28, 24, 22, 18,

	20, 24, 18, 18, 27, 22, 23, 18, 27, 21,

	28, 22, 23, 26, 27, 21, 20, 20, 21, 27,

	22, 19, 31, 30, 18, 15, 28, 25, 24, 22,

	24, 19, 24, 27, 24, 26);

	var wer = new Array(

	"n", "y", "n", "y", "y", "n", "y", "y", "y", "y",

	"y", "y", "y", "n", "y", "y", "y", "n", "y", "n",

	"y", "y", "n", "y", "y", "y", "y", "y", "y", "n",

	"n", "n", "y", "n", "y", "y", "y", "y", "y", "n",

	"y", "y", "y", "n", "n", "n");

	if (tempStr != '') {

		// krok 0 pozostaw cyfry i litery A-Z a-z, zamien na UpperCase

		// jesli na pocz. brak PL to tam wpisz

		tempStr = compactNonAlfa(tempStr); // alert( tempStr );

		if ((tempStr.charAt(0) <= '9') && (tempStr.charAt(1) <= '9'))

			tempStr = 'PL' + tempStr;

		kopia = tempStr;

		// krok 0 weryfikacja dlugosci IBAN

		numer = -1;

		kodkraju = tempStr.substring(0, 2);

		for ( var i = 0; i < kod.length; i++) {

			if (kodkraju == kod[i]) {
				numer = i;

				// alert('kod kraju = ' + kod[i]+' dlugosc IBAN = '+dlu[i] );

			} // if

		}// for

		if (numer == -1) {

			return ' Błędny kod kraju';

			return false;

		}

		if (numer >= 0 && dlu[numer] != tempStr.length) {

			return ' Błędna długość kodu';

			return false;

		}

		// krok 1 przesun 4 pierwsze znaki na koniec

		tempStr = tempStr.substring(4, tempStr.length)
				+ tempStr.substring(0, 4);

		// krok 2 zamien litery na cyfry

		for ( var i = 0; i < tempStr.length; i++) { // alert( i );

			znak = tempStr.charAt(i);

			// alert('tempStr='+tempStr.substring(0,i) +'?'+
			// tempStr.substring(i+1,tempStr.length));

			if ((znak >= 'A') && (znak <= 'Z')) {

				ca = CodeOf(znak);

				tempStr = tempStr.substring(0, i) + ca
						+ tempStr.substring(i + 1, tempStr.length);

			} // if

		}// for uwaga: łańcuch się wydłuża gdy sš litery

		// krok 3 podziel modulo 97; dana testowa BE62 5100 0754 7061 powinno
		// wyjsc 1

		mod = 0;
		L = tempStr.length;

		for ( var i = 0; i < L; i++) {

			// alert('mod=' + ' ' + mod + ' kolejny znak=' + tempStr.substr( i,
			// 1) );

			// Uwaga: Netscape i IExpolorerze w parseInt traktuje 011 jako zapis
			// oktalny

			// wiec lepiej wymusic traktowanie dziesietne

			mod = parseInt('' + mod + tempStr.substr(i, 1), 10) % 97;

		}

		// krok 4

		return ((mod == 1) ? ' = Dobry' : ' = Błędny ');

	}

	else

	{
		return ' Brak danych';

	}

	return false;

} // fun verifyIBAN

function formInit() {

}

function compactString() { // analyze input string

	var inpstr = compactString.arguments[0]; // document.forms[0].dane.value;

	var inpstl = inpstr.length; // document.forms[0].dane.length;

	// keyboard=true;

	for (i = 0; i < inpstr.length; i++) {

		znak = inpstr.charAt(i);

		// alert('inpStr='+inpstr.substring(0,i) +'?'+
		// inpstr.substring(i+1,inpstr.length));

		if ((znak < '0' || znak > '9') && (znak != 'X') && (znak != 'x')) {

			inpstr = inpstr.substring(0, i)
					+ inpstr.substring(i + 1, inpstr.length);

			i = i - 1;

		}

		// alert('inpStr='+inpstr);

	}

	return inpstr;

}

function verifyEURO() { /* copyright R.J.Żyłła 2003-2023 */

	var tempStr = verifyEURO.arguments[0]; // alert( verifyEURO.arguments[0] );

	// TU wstawić przydział kraju do litery banknotu

	// testowy numer 50EURO X38271664604

	var kod = new Array

	('E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',

	'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

	var kraj = new Array

	('Słowacja', 'Malta', 'Cypr', 'Słowenia', 'litera nie używana',

	'(Wielka Brytania)', '(Szwecja)', 'Finlandia', 'Portugalia', 'Austria',

	'litera nie używana', 'Holandia', 'litera nie używana', '(Luxembourg)',
			'Włochy',

			'Irlandia', 'Francja', 'Hiszpania', '(Dania)', 'Niemcy', 'Grecja',
			'Belgia');

	// alert('proc verifyEURO() ');

	if (tempStr != '') {

		// krok 0 pozostaw cyfry i litery A-Z a-z, zamien na UpperCase

		tempStr = compactNonAlfa(tempStr);

		kopia = tempStr;

		// weryfikacja litery numeru EURO

		numer = -1;

		kodkraju = tempStr.substring(0, 1);

		// alert('kod kraju=' + kodkraju );

		for ( var i = 0; i < kod.length; i++) {

			if (kodkraju == kod[i])
				numer = i;

		}// for

		// alert('kod kraju = '+ kodkraju + '   kraj=' + kraj[numer] );

		if (numer == -1) {

			return false;

		} else
			druk = 'miejsce druku banknotu: ' + kraj[numer];

		if (numer >= 0 && tempStr.length != 12) {

			return false;

		}

		// zamien literę na liczbę

		znak = tempStr.charAt(0);

		// alert('znak='+znak + ' ASCII kod='+ tempStr.charCodeAt(0) );

		if ((znak >= 'A') && (znak <= 'Z'))
			ca = tempStr.charCodeAt(0) - 64; // if

		// alert('znak='+znak +' ASCII code ca='+ca );

		L = tempStr.length;
		suma = ca;

		for ( var i = 1; i < L; i++) {

			// Uwaga: Netscape i IExpolorerze w parseInt traktuje 011 jako zapis
			// oktalny

			// wiec lepiej wymusic traktowanie dziesietne

			// alert('kolejna='+ parseInt( tempStr.substr( i, 1),10 ) );

			ca += parseInt(tempStr.substr(i, 1), 10);

		}

		// reszta z dzielenia powinna wyjść 8, reszta 0 jest pokazana jako 9

		ca = ((ca - 1) % 9) + 1;

		return ((ca == 8) ? ' = OK ' : ' = Błędny ');

	}

	else
		return ' Brak danych';

	return false;

} // fun verifyEURO
