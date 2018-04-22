load data
  local infile "bigrecords.txt"
  replace into table p1records
  fields terminated by '|'
  (itemnum, authors, title, publication, year, type, url);