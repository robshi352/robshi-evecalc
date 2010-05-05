CREATE TABLE pecoInvention(
	inventionID int(11) NOT NULL,
	inventorID int(11) NOT NULL,
	entryDate datetime default NULL,
	outputTypeID int(11) NOT NULL,
	completedStatus tinyint(1),
	PRIMARY KEY (inventionID)
) DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

CREATE TABLE pecoMemberID(
	memberID int(11) NOT NULL,
	memberName char(50) NOT NULL,
	PRIMARY KEY (memberID)
) DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;