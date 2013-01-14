SELECT a.strm,
       a.session_code,
       a.subject,
       a.catalog_nbr,
       a.class_stat,
       a.enrl_cap,
       a.enrl_tot,
       b.ssr_component
FROM  octsims.erp805_class_section a,
      octsims.erp805_course_component b
WHERE a.crse_id = b.crse_id
