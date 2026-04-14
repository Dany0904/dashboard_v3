/* eslint-disable no-console */

define(["jquery", "core/ajax", "local_dashboard_v3/apexcharts"], function (
  $,
  Ajax,
  ApexCharts,
) {
  return {
    init: function () {
        const urlParams = new URLSearchParams(window.location.search);
        let days = parseInt(urlParams.get("days")) || 30;

        const filter = document.getElementById("filter-days");

        if (filter) {
          filter.addEventListener("change", function () {
            window.location.href = "?days=" + this.value;
          });
        }

        const requests = Ajax.call([
          {
            methodname: "local_dashboard_v3_get_dashboard_v3_charts",
            args: { days: parseInt(days) },
          },
          {
            methodname: "local_dashboard_v3_get_dashboard_v3_tables",
            args: { days: parseInt(days) },
          },
        ]);

        $.when(requests[0], requests[1]).done(function (chartsRes, tablesRes) {
          const charts = chartsRes;
          /**
           * Generate color array for chart.
           *
           * @param {number} n Number of colors needed.
           * @returns {Array} Array of color strings.
           */
          function generateColors(n) {
            const baseColors = [
              "#008FFB",
              "#00E396",
              "#FEB019",
              "#FF4560",
              "#775DD0",
              "#3F51B5",
              "#546E7A",
              "#26a69a",
              "#D10CE8",
              "#FF9800",
            ];
            return baseColors.slice(0, n);
          }

          // =======================
          // Cursos Demandados
          // =======================

          let enrollLabels = [];
          let enrollValues = [];

          charts.enrollments.forEach((item) => {
            enrollLabels.push(item.name);
            enrollValues.push(item.total);
          });

          if (window.enrollmentsChartInstance) {
            window.enrollmentsChartInstance.destroy();
          }

          const optionsEnroll = {
            chart: {
              type: "donut",
              height: 350,
            },
            series: enrollValues,
            labels: enrollLabels,
            colors: generateColors(enrollLabels.length),
            legend: {
              position: "bottom",
            },
            dataLabels: {
              enabled: true,
              formatter: function (val) {
                return val.toFixed(1) + "%";
              },
            },
            tooltip: {
              y: {
                formatter: function (value) {
                  return value + " inscritos";
                },
              },
            },
          };

          window.enrollmentsChartInstance = new ApexCharts(
            document.querySelector("#inscritosChart"),
            optionsEnroll,
          );

          window.enrollmentsChartInstance.render();

          // =======================
          // Cursos con mejores Promedios
          // =======================

          let avgLabels = [];
          let avgValues = [];

          charts.averages.forEach((item) => {
            avgLabels.push(item.name);
            avgValues.push(item.average);
          });

          if (window.promedioChartInstance) {
            window.promedioChartInstance.destroy();
          }

          const optionsPromedio = {
            chart: {
              type: "bar",
              height: 350,
            },
            series: [
              {
                name: "Promedio",
                data: avgValues,
              },
            ],
            xaxis: {
              categories: avgLabels,
            },
            colors: generateColors(avgLabels.length),
            plotOptions: {
              bar: {
                borderRadius: 6,
                columnWidth: "60%",
              },
            },
            dataLabels: {
              enabled: true,
              formatter: (val) => val.toFixed(1),
            },
            tooltip: {
              y: {
                formatter: (val) => val.toFixed(2),
              },
            },
          };

          window.promedioChartInstance = new ApexCharts(
            document.querySelector("#promedioChart"),
            optionsPromedio,
          );

          window.promedioChartInstance.render();

          // ===============================
          // ACTIVIDAD DE USUARIOS
          // ===============================

          let activityLabels = [];
          let activityData = [];

          charts.activity.forEach(function (item) {
            activityLabels.push(item.label);
            activityData.push(item.value);
          });

          // destruir si ya existe
          if (window.activityChartInstance) {
            window.activityChartInstance.destroy();
          }

          var activityOptions = {
            chart: {
              type: "area",
              height: 350,
              toolbar: { show: true },
            },
            series: [
              {
                name: "Usuarios Activos",
                data: activityData,
              },
            ],
            xaxis: {
              categories: activityLabels,
              title: { text: "Día" },
            },
            yaxis: {
              title: { text: "Usuarios" },
            },
            stroke: {
              curve: "smooth",
              width: 2,
            },
            dataLabels: {
              enabled: false,
            },
            fill: {
              type: "gradient",
              gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
              },
            },
            tooltip: {
              y: {
                formatter: function (val) {
                  return val + " usuarios";
                },
              },
            },
            colors: ["#1cc88a"]
          };

          window.activityChartInstance = new ApexCharts(
            document.querySelector("#activityChart"),
            activityOptions
          );

          window.activityChartInstance.render();

          // ===============================
          //  ABANDONO DE CURSOS
          // ===============================

          let abandonLabels = [];
          let abandonData = [];

          charts.abandon.forEach(function (item) {
            abandonLabels.push(item.label);
            abandonData.push(item.value);
          });

          if (window.abandonChartInstance) {
            window.abandonChartInstance.destroy();
          }

          var abandonOptions = {
            chart: {
              type: "area",
              height: 350,
              toolbar: { show: true },
            },
            series: [
              {
                name: "% Abandono",
                data: abandonData,
              },
            ],
            xaxis: {
              categories: abandonLabels,
              title: { text: "Día" },
            },
            yaxis: {
              labels: {
                formatter: function (val) {
                  return val + "%";
                },
              },
              title: { text: "Porcentaje" },
            },
            stroke: {
              curve: "smooth",
              width: 2,
            },
            fill: {
              type: "gradient",
              gradient: {
                opacityFrom: 0.5,
                opacityTo: 0.1,
              },
            },
            colors: ["#e74a3b"],
            tooltip: {
              y: {
                formatter: function (val) {
                  return val + "% abandono";
                },
              },
            },
          };

          window.abandonChartInstance = new ApexCharts(
            document.querySelector("#abandonChart"),
            abandonOptions
          );

          window.abandonChartInstance.render();

           // ===============================
          //  CURSOS POPULARES
          // ===============================

          let courseLabels = [];
          let courseData = [];

          charts.popularcourses.forEach(function (item) {
            courseLabels.push(item.label);
            courseData.push(item.value);
          });

          if (window.popularCoursesChartInstance) {
            window.popularCoursesChartInstance.destroy();
          }

          var popularOptions = {
            chart: {
              type: "bar",
              height: 350,
            },
            series: [{
              name: "Actividad",
              data: courseData
            }],
            xaxis: {
              categories: courseLabels,
            },
            plotOptions: {
              bar: {
                horizontal: true
              }
            },
            colors: ["#36b9cc"],
          };

          window.popularCoursesChartInstance = new ApexCharts(
            document.querySelector("#popularCoursesChart"),
            popularOptions
          );

          window.popularCoursesChartInstance.render();

          // ===============================
          // ACTIVIDAD DOCENTES
          // ===============================

          let teacherLabels = [];
          let teacherData = [];

          charts.teachers.forEach(function (item) {
            teacherLabels.push(item.label);
            teacherData.push(item.value);
          });

          if (window.teachersChartInstance) {
            window.teachersChartInstance.destroy();
          }

          var teacherOptions = {
            chart: {
              type: "line",
              height: 350,
            },
            series: [{
              name: "Docentes activos",
              data: teacherData
            }],
            xaxis: {
              categories: teacherLabels,
            },
            stroke: {
              curve: "smooth"
            },
            colors: ["#f6c23e"], // amarillo = docentes
          };

          window.teachersChartInstance = new ApexCharts(
            document.querySelector("#teachersChart"),
            teacherOptions
          );

          window.teachersChartInstance.render();
        })
        .fail(function (error) {
          console.error(error);
        });
    },
  };
});
