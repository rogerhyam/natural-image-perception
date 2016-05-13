
# show break down of naturalness scoring

SELECT calc_naturalness, count(*) FROM `image` 
where path like '%SV_%'
group by calc_naturalness order by calc_naturalness 

SELECT calc_artificialness, count(*) FROM `image` 
where path like '%SV_%'
group by calc_artificialness order by calc_artificialness 
