
chown -R www-data .;
chgrp -R www-data .;

rm -f cache/module_authorization_settings.dat;
rm -f cache/zend_cache*;

if [ ! -L "library/mpdf60" ]
then
    ln -s /var/www/library/mpdf60 library/;
fi

if [ ! -L "library/TCPDF" ]
then
    ln -s /var/www/library/TCPDF library/;
fi

if [ ! -L "library/PHPExcel" ]
then
    ln -s /var/www/library/PHPExcel library/;
fi

if [ ! -L "library/Zend" ]
then
    ln -s /var/www/library/Zend library/;
fi

if [ ! -L "web/assets" ]
then
    ln -s /var/www/library/assets web/;
fi

