

/*==============================================================*/
/* Table: retry_interval                                        */
/*==============================================================*/

   CREATE TABLE IF NOT EXISTS retry_interval (
     retry_interval VARCHAR(200)
    ) ENGINE = InnoDB;
 
 INSERT INTO retry_interval (retry_interval)
 SELECT '60' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM retry_interval);

