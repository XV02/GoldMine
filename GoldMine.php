<?php
  set_time_limit(0);
  if(file_exists("C:/GoldMineLogBalance/TransferLog.txt"))
  {
    unlink("C:/GoldMineLogBalance/TransferLog.txt");
  }
  $logfile = fopen("C:/GoldMineLogBalance/TransferLog.txt", "w");
  date_default_timezone_set("America/Los_Angeles");
  $curdate = date("d/m/Y H:i:s");
  fwrite($logfile, $curdate.PHP_EOL);

  $serverName = "10.10.100.8\MSSQLSERVER, 1433";
  $connectionInfo = array( "Database"=>"CoSS07", "UID"=>"cr", "PWD"=>"s0ftc917RO", 'ReturnDatesAsStrings'=> true, "CharacterSet" => 'utf-8');
  $Econn = sqlsrv_connect( $serverName, $connectionInfo);
  if($Econn)
  {
    $Gconn = odbc_connect("Driver={SQL Server Native Client 10.0};Server=10.10.100.5;Database=GoldMine_Sales_and_Marketing;", "cr", "1234");
    if($Gconn)
    {
      $stmt = sqlsrv_query($Econn, "SELECT CustomerNumber, CustomerID, (Invoices-Unapplied) AS Balance  FROM ARCustomers WHERE Active = 1");
      while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
      {
        $customerNumber = $row['CustomerNumber'];
        $check = odbc_exec($Gconn, "SELECT * FROM dbo.CONTACT2 WHERE UCUSTID = '$customerNumber'");
        if(odbc_num_rows($check) > 0)
        {
          $customerID = $row['CustomerID'];
          $balance = $row['Balance'];
          $curdate = date("Y-m-d");
          $lastDate = "";
          $lastDateQuery = sqlsrv_query($Econn, "SELECT MAX(Date) AS Date FROM ARReceipts WHERE CustomerID = $customerID");
          $date = sqlsrv_fetch_array($lastDateQuery);
          $lastDate = $date['Date'];
          if(strlen($lastDate) > 0)
          {
            $update = odbc_exec($Gconn, "UPDATE dbo.CONTACT2 SET USERDEF08 = $balance, ULAST_PYM_ = '$lastDate', UFF = '$curdate' WHERE UCUSTID = '$customerNumber'");
          }
          else
          {
            $update = odbc_exec($Gconn, "UPDATE dbo.CONTACT2 SET USERDEF08 = $balance, UFF = '$curdate' WHERE UCUSTID = '$customerNumber'");
            fwrite($logfile, $customerNumber." not dated".PHP_EOL);
          }
          if(!$update)
          {
            fwrite($logfile, $customerNumber." failed to transfer".PHP_EOL);
          }
        }
        else
        {
          fwrite($logfile, $customerNumber." customer not found".PHP_EOL);
        }
      }
      fwrite($logfile, "TRANSFER ENDED".PHP_EOL);
    }
    else
    {
      fwrite($logfile, "FAILED TO CONNECT TO GOLDMINE DATABASE".PHP_EOL);
    }
  }
  else
  {
    fwrite($logfile, "FAILED TO CONNECT TO E-AUTOMATE DATABASE".PHP_EOL);
  }
  fclose($logfile);
  sqlsrv_close($Econn);
  odbc_close_all();
?>
