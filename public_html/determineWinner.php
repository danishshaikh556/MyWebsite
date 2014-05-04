<?php
/*******************************************************************
* TITLE: determineWinner.php
* PURPOSE: This file receives POST data in the following format:
*   USER_NAME:FIGHT_USER_NAME|AGILITY ARM_STR LEG_STR HEALTH DEFENSE
*   it parses through it, retrieves data from the database, and 
*   determines a winner 
********************************************************************/
	//separates the user names from stats
	$fightDataParts = explode('|', $_POST['fightData']);

	//parses usernames from post data
	$userNameParts = explode(':', $fightDataParts[0]);
	$user1 = $userNameParts[0];
	$user2 = $userNameParts[1];

	//check to see if we are only updating or also fighting, if we are, exit script
	//NOTE: HMON1234UPDTE5678 is a username we will need to block from being entered
	if($user2 == "HMON1234UPDTE5678"){
		$updatedUserInfo = $user1.":".$fightDataParts[1];
		writeUsersStatsToDatabase($user1, $updatedUserInfo);
		exit;
	}

	//parses user 1 stats from post data
	$u1StatParts = explode(' ', $fightDataParts[1]);
	$u1AgilLvl = $u1StatParts[0];
	$u1AgilExp = $u1StatParts[1];
	$u1AstrLvl = $u1StatParts[2];
	$u1AstrExp = $u1StatParts[3];
	$u1LstrLvl = $u1StatParts[4];
	$u1LstrExp = $u1StatParts[5];
	$u1DefLvl = $u1StatParts[6];
	$u1DefExp = $u1StatParts[7];
	$u1HlthLvl = $u1StatParts[8];
	$u1HlthExp = $u1StatParts[9];

	//write the user who posted this data's info the database
	$updatedUserInfo = $user1.":".$fightDataParts[1];
	writeUsersStatsToDatabase($user1, $updatedUserInfo);

	//get user 2 stats from our database
	//if user 2 is "RNDM23456" we get a random user to fight
	if($user2 == "RNDM23456"){
		$userInfoParts = explode(':', getRandomUserData($user1));
		$user2 = $userInfoParts[0];
		$u2StatParts = $userInfoParts[1];
	}else{
		$u2StatParts = getUserTwoData($user2);
	}
	
	if($user1 == $user2 || empty($u2StatParts)){ //user name not found or user tries to fight themselves
		if($user1 == $user2)
			echo "ERROR: You cannot fight yourself!";
		else
			echo "ERROR: ".$user2." is not a valid user name! Try fighting a random opponent.";
	}else{ //user name is found, calculate data
		$u2AgilLvl = $u2StatParts[1];
		$u2AgilExp = $u2StatParts[2];
		$u2AstrLvl = $u2StatParts[3];
		$u2AstrExp = $u2StatParts[4];
		$u2LstrLvl = $u2StatParts[5];
		$u2LstrExp = $u2StatParts[6];
		$u2DefLvl = $u2StatParts[7];
		$u2DefExp = $u2StatParts[8];
		$u2HlthLvl = $u2StatParts[9];
		$u2HlthExp = $u2StatParts[10];

		//calculation are performed on the stats that are pulled from our database.
		//these are passed into determineWinner function
		$u1Agil = $u1AgilLvl*100 + $u1AgilExp;
		$u1Astr = $u1AstrLvl*100 + $u1AstrExp;
		$u1Lstr = $u1LstrLvl*100 + $u1LstrExp;
		$u1Def = $u1DefLvl*100 + $u1DefExp;
		$u1Hlth = $u1HlthLvl*100 + $u1HlthExp;
		$u2Agil = $u2AgilLvl*100 + $u2AgilExp;
		$u2Astr = $u2AstrLvl*100 + $u2AstrExp;
		$u2Lstr = $u2LstrLvl*100 + $u2LstrExp;
		$u2Def = $u2DefLvl*100 + $u2DefExp;
		$u2Hlth = $u2HlthLvl*100 + $u2HlthExp;

		$won = determineWinner($u1Agil, $u1Astr, $u1Lstr, $u1Def, $u1Hlth, $u2Agil, $u2Astr, $u2Lstr, $u2Def, $u2Hlth);
		if($won)
			echo "You won the fight against ".$user2."\n";
		else
			echo "You lost the fight against ".$user2."\n";
	}


	/***********************
    * FUNCTIONS START HERE *
    ************************/

	//gets data from databse for a specified user. 
	//the first value in array will be empty if user is not found, this is used for errors checking
	function getUserTwoData($userToFight){
		$userString = file_get_contents("./userLog.txt");
		$expression = "/".$userToFight.".*?(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/";
		preg_match($expression, $userString, $match);
		return $match;
	}
	
	//grabs a random user from the text file and returns their data 
	//pass it the name of the user who is fighting so we know we don't grab him
	function getRandomUserData($user1){
		$userArray = file("./userLog.txt");
		$userArrayCount = count($userArray);
		$user2 = $user1;
		while($user1 == $user2){
			$randomIndex = rand(0, $userArrayCount-1);
			$randomUser = $userArray[$randomIndex];
			$randomUserParts = explode(':', $randomUser);
			$user2 = $randomUserParts[0];
		}
		return $randomUser;
	}

	//writes data the user posted to the server to the database
	//also adds users who are not currently in database (first time users) to it
	function writeUsersStatsToDatabase($user1, $updatedUserInfo){
		$userString = file_get_contents("./userLog.txt");
		$expression = "/".$user1.":\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+/";
		$newData = preg_replace($expression, $updatedUserInfo, $userString, -1, $count);
			   
	   //nothing has changed, user is not in database, add them to it
	   //count will be 0 if nothing has changed
	   if($count == 0){ 
		   $newData .= $updatedUserInfo."\n";
	   }
	   file_put_contents("./userLog.txt", $newData);
	}

	//user 1 is the user we who STARTS the fight (this is crucial)
	//user 1 has a 65% chance of winning at the beginning, can gain or lose their change to win
	//NOTE: This is based somewhat on luck, but primarily on each users stats
	function determineWinner($u1Agil, $u1Astr, $u1Lstr, $u1Def, $u1Hlth, $u2Agil, $u2Astr, $u2Lstr, $u2Def, $u2Hlth){
		//starts at 65% can be anywhere between 0 - 100
		$chanceToWin = 65;

		//increase or decrease chance to win based on stat differences
		if($u1Agil >= ($u2Agil*2))
			$chanceToWin += 7;
		else
			$chanceToWin += (($u1Agil - $u2Agil) / $u2Agil * 7);

		if($u1Astr >= ($u2Astr*2))
			$chanceToWin += 7;
		else
			$chanceToWin += (($u1Astr - $u2Astr) / $u2Astr * 7);
	
		if($u1Lstr >= ($u2Lstr*2))
			$chanceToWin += 7;
		else
			$chanceToWin += (($u1Lstr - $u2Lstr) / $u2Lstr * 7);

		if($u1Def >= ($u2Def*2))
			$chanceToWin += 7;
		else
			$chanceToWin += (($u1Def - $u2Def) / $u2Def * 7);

		if($u1Hlth >= ($u2Hlth*2))
			$chanceToWin += 7;
		else	
			$chanceToWin += (($u1Hlth - $u2Hlth) / $u2Hlth * 7);

		//generate random number from 0-100 to compare against chance to win
		$randomNumber = rand(0, 100);

		/*
		echo "u1Agil: ".$u1Agil."<br>u1Astr: ".$u1Astr."<br>u1Lstr: ".$u1Lstr."<br>u1Def: ".$u1Def."<br>u1Hlth: ".$u1Hlth."<br><br>";
		echo "u2Agil: ".$u2Agil."<br>u2Astr: ".$u2Astr."<br>u2Lstr: ".$u2Lstr."<br>u2Def: ".$u2Def."<br>u2Hlth: ".$u2Hlth."<br><br>";
		echo "Chance To Win: ".$chanceToWin."<br>Random Number: ".$randomNumber."<br><br>";
		*/

		//if randome number is less than or equal to chance to win, user 1 wins
		if($randomNumber <= $chanceToWin)
			return true; //user 1 has won
		else
			return false; //user 1 has lost
	}
?>