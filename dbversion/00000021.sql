CREATE TABLE `bar_code_reader` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`name`  text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`is_used`  tinyint(4) NULL DEFAULT NULL ,
`related_document_id`  int(11) NULL DEFAULT NULL ,
`related_document_type`  text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL ,
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci
ROW_FORMAT=Compact
;