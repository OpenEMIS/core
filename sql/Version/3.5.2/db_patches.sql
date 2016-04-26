ALTER TABLE `db_patches` ADD `version` VARCHAR(15) NULL AFTER `issue`;

set @earliest := 0;
select min(created) into @earliest from db_patches where created <> '0000-00-00 00:00:00';
update db_patches set created = date(@earliest) where created = '0000-00-00 00:00:00';

INSERT INTO db_patches (issue, version, created) VALUES ('PHPOE-1347', '3.2.2', @earliest);

UPDATE db_patches SET version = '3.4.18.2' WHERE issue = 'POCOR-2786';
UPDATE db_patches SET version = '3.5.1' WHERE issue = 'POCOR-2172';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-2749';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-2675';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-1694';
UPDATE db_patches SET version = '3.4.18' WHERE issue = 'POCOR-2733';
UPDATE db_patches SET version = '3.4.17' WHERE issue = 'POCOR-2604';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2658';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2609';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-1905';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2208';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2540';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-2562';
UPDATE db_patches SET version = '3.4.16' WHERE issue = 'POCOR-1798';
UPDATE db_patches SET version = '3.4.15a' WHERE issue = 'POCOR-2683';
UPDATE db_patches SET version = '3.4.15a' WHERE issue = 'POCOR-2612';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2608';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2446';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2601';
UPDATE db_patches SET version = '3.4.15' WHERE issue = 'POCOR-2445';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2564';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2571';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2014';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-1968';
UPDATE db_patches SET version = '3.4.14' WHERE issue = 'POCOR-2491';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2539';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'PHPOE-2535';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2489';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2515';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2526';
UPDATE db_patches SET version = '3.4.13' WHERE issue = 'POCOR-2392';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2497';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2501';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2465';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2232';
UPDATE db_patches SET version = '3.4.12' WHERE issue = 'POCOR-2506';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-1508';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-2484';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-2500';
UPDATE db_patches SET version = '3.4.11' WHERE issue = 'PHPOE-2505';
UPDATE db_patches SET version = '3.4.10' WHERE issue = 'PHPOE-2168';
UPDATE db_patches SET version = '3.4.10' WHERE issue = 'PHPOE-2423';
UPDATE db_patches SET version = '3.4.10' WHERE issue = 'PHPOE-2433';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-2436';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-2463';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-2023';
UPDATE db_patches SET version = '3.4.9' WHERE issue = 'PHPOE-1787';
UPDATE db_patches SET version = '3.4.8' WHERE issue = 'PHPOE-2435';
UPDATE db_patches SET version = '3.4.8' WHERE issue = 'PHPOE-2338';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-2291';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-832';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-1227';
UPDATE db_patches SET version = '3.4.7' WHERE issue = 'PHPOE-1808';
UPDATE db_patches SET version = '3.4.6' WHERE issue = 'PHPOE-2421';
UPDATE db_patches SET version = '3.4.5' WHERE issue = 'PHPOE-1903';
UPDATE db_patches SET version = '3.4.5' WHERE issue = 'PHPOE-2198';
UPDATE db_patches SET version = '3.4.4' WHERE issue = 'PHPOE-2403';
UPDATE db_patches SET version = '3.4.3' WHERE issue = 'PHPOE-1420';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-2366';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-2319';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-2359';
UPDATE db_patches SET version = '3.4.2' WHERE issue = 'PHPOE-1961';
UPDATE db_patches SET version = '3.4.1' WHERE issue = 'PHPOE-2257';
UPDATE db_patches SET version = '3.4.1' WHERE issue = 'PHPOE-2193';
UPDATE db_patches SET version = '3.4.1' WHERE issue = 'PHPOE-1463';
UPDATE db_patches SET version = '3.3.8' WHERE issue = 'PHPOE-2298';
UPDATE db_patches SET version = '3.3.8' WHERE issue = 'PHPOE-2310';
UPDATE db_patches SET version = '3.3.8' WHERE issue = 'PHPOE-2250';
UPDATE db_patches SET version = '3.3.7' WHERE issue = 'PHPOE-2069';
UPDATE db_patches SET version = '3.3.7' WHERE issue = 'PHPOE-2086';
UPDATE db_patches SET version = '3.3.7' WHERE issue = 'PHPOE-1978';
UPDATE db_patches SET version = '3.3.6' WHERE issue = 'PHPOE-1707';
UPDATE db_patches SET version = '3.3.5' WHERE issue = 'PHPOE-1902-2';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2084';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2099';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2281';
UPDATE db_patches SET version = '3.3.4' WHERE issue = 'PHPOE-2305';
UPDATE db_patches SET version = '3.3.1' WHERE issue = 'PHPOE-2248';
UPDATE db_patches SET version = '3.3.1' WHERE issue = 'PHPOE-1992';
UPDATE db_patches SET version = '3.2.10' WHERE issue = 'PHPOE-2233';
UPDATE db_patches SET version = '3.2.7' WHERE issue = 'PHPOE-680';
UPDATE db_patches SET version = '3.2.6' WHERE issue = 'PHPOE-2225';
UPDATE db_patches SET version = '3.2.6' WHERE issue = 'PHPOE-1352';
UPDATE db_patches SET version = '3.2.4' WHERE issue = 'PHPOE-2092';
UPDATE db_patches SET version = '3.2.4' WHERE issue = 'PHPOE-2178';
UPDATE db_patches SET version = '3.2.4' WHERE issue = 'PHPOE-1904';
UPDATE db_patches SET version = '3.2.5' WHERE issue = 'PHPOE-2081';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2144';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2124';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2078';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2028';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-2103';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-1430';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-1414';
UPDATE db_patches SET version = '3.2.3' WHERE issue = 'PHPOE-1381';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-1346';
UPDATE db_patches SET version = '3.1.5' WHERE issue = 'PHPOE-1391';
UPDATE db_patches SET version = '3.1.4' WHERE issue = 'PHPOE-1573';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1592';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1657';
UPDATE db_patches SET version = '3.1.1' WHERE issue = 'PHPOE-1741';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1762';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1799';
UPDATE db_patches SET version = '3.1.3' WHERE issue = 'PHPOE-1807';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1815';
UPDATE db_patches SET version = '3.0.6' WHERE issue = 'PHPOE-1821';
UPDATE db_patches SET version = '3.0.8' WHERE issue = 'PHPOE-1825';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1857';
UPDATE db_patches SET version = '3.0.9' WHERE issue = 'PHPOE-1878';
UPDATE db_patches SET version = '3.0.8' WHERE issue = 'PHPOE-1882';
UPDATE db_patches SET version = '3.1.5' WHERE issue = 'PHPOE-1892';
UPDATE db_patches SET version = '3.1.3' WHERE issue = 'PHPOE-1896';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1900';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1902';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1916';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-1919';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1933';
UPDATE db_patches SET version = '3.1.2' WHERE issue = 'PHPOE-1948';
UPDATE db_patches SET version = '3.2.1' WHERE issue = 'PHPOE-1982';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2016';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2019';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2036';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2063';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2072';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2088';
UPDATE db_patches SET version = '3.2.2' WHERE issue = 'PHPOE-2117';

